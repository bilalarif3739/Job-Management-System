<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Company;
use App\Models\EmailInvitation;
use App\Models\Team;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationChannelProvisioningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mongodb') {
            $this->markTestSkipped('MongoDB is required for registration provisioning tests.');
        }
    }

    public function test_new_registration_provisions_announcement_channel_and_membership(): void
    {
        $result = app(RegistrationService::class)->register([
            'name' => 'New Owner',
            'email' => 'new-owner-' . uniqid() . '@example.com',
            'password' => 'Password123!',
            'company' => 'New Company ' . uniqid(),
        ]);

        $user = $result['user'];
        $team = Team::where('company_id', (string) $user->company_ids[0])
            ->where('name', 'General Team')
            ->firstOrFail();
        $channel = Channel::where('team_id', (string) $team->getKey())
            ->where('name', Channel::DEFAULT_ANNOUNCEMENT_NAME)
            ->firstOrFail();
        $membership = ChannelMember::where('channel_id', (string) $channel->getKey())
            ->where('joined_by', (string) $user->getKey())
            ->whereNull('deleted_at')
            ->firstOrFail();

        $this->assertSame((string) $user->getKey(), (string) $channel->creator_id);
        $this->assertSame('everyone', $channel->message_permission);
        $this->assertSame([], $channel->allowed_sender_ids);
        $this->assertSame((string) $user->getKey(), (string) $membership->added_by);
        $this->assertSame((string) $user->getKey(), (string) $membership->joined_by);
    }

    public function test_invitation_registration_uses_inviter_as_added_by(): void
    {
        [$company, $team, $inviter] = $this->createInvitedCompany();
        $token = 'registration-' . uniqid();
        EmailInvitation::create([
            'user_id' => (string) $inviter->getKey(),
            'company_id' => (string) $company->getKey(),
            'invitation' => 1,
            'token' => $token,
            'expires_at' => now()->addDay(),
        ]);

        $result = app(RegistrationService::class)->register([
            'name' => 'Invited User',
            'email' => 'invited-' . uniqid() . '@example.com',
            'password' => 'Password123!',
        ], $token);

        $user = $result['user'];
        $channel = Channel::where('team_id', (string) $team->getKey())->firstOrFail();
        $membership = ChannelMember::where('channel_id', (string) $channel->getKey())
            ->where('joined_by', (string) $user->getKey())
            ->whereNull('deleted_at')
            ->firstOrFail();

        $this->assertSame((string) $inviter->getKey(), (string) $membership->added_by);
    }

    public function test_invitation_login_does_not_duplicate_active_channel_membership(): void
    {
        [$company, $team, $inviter] = $this->createInvitedCompany();
        $user = User::create([
            'name' => 'Existing User',
            'email' => 'existing-' . uniqid() . '@example.com',
            'password' => 'Password123!',
        ]);
        $token = 'login-' . uniqid();
        EmailInvitation::create([
            'user_id' => (string) $inviter->getKey(),
            'company_id' => (string) $company->getKey(),
            'invitation' => 1,
            'token' => $token,
            'expires_at' => now()->addDay(),
        ]);

        $service = app(RegistrationService::class);
        $service->login($user, $token);
        $service->login($user, $token);

        $channel = Channel::where('team_id', (string) $team->getKey())->firstOrFail();

        $this->assertSame(
            1,
            ChannelMember::where('channel_id', (string) $channel->getKey())
                ->where('joined_by', (string) $user->getKey())
                ->whereNull('deleted_at')
                ->count()
        );
    }

    /**
     * @return array{Company, Team, User}
     */
    private function createInvitedCompany(): array
    {
        $inviter = User::create([
            'name' => 'Inviter',
            'email' => 'inviter-' . uniqid() . '@example.com',
            'password' => 'Password123!',
        ]);
        $company = Company::create([
            'name' => 'Invited Company ' . uniqid(),
            'creator_id' => (string) $inviter->getKey(),
            'user_ids' => [(string) $inviter->getKey()],
        ]);
        $team = Team::create([
            'company_id' => (string) $company->getKey(),
            'creator_id' => (string) $inviter->getKey(),
            'name' => 'General Team',
            'user_ids' => [(string) $inviter->getKey()],
        ]);
        Channel::create([
            'company_id' => (string) $company->getKey(),
            'team_id' => (string) $team->getKey(),
            'creator_id' => (string) $inviter->getKey(),
            'name' => Channel::DEFAULT_ANNOUNCEMENT_NAME,
            'type' => 'public',
        ]);

        return [$company, $team, $inviter];
    }
}
