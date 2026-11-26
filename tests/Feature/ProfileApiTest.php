<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthToken;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('profile_picture_path')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('remembertoken')->nullable();
            $table->unsignedBigInteger('last_activity')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('sqlite')->create('user_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('token_hash', 100)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_user_can_view_and_update_profile_without_email_verification(): void
    {
        $user = $this->createUser('old@example.com');
        $verifiedAt = $user->email_verified_at;

        $this->withoutMiddleware(AuthToken::class);
        $this->actingAs($user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'old@example.com')
            ->assertJsonPath('data.user.profile_picture_url', null);

        $this->patchJson('/api/profile', [
            'name' => 'Updated User',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Updated User');

        $this->patchJson('/api/profile', [
            'email' => 'new@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'new@example.com');

        $this->assertSame(
            $verifiedAt->timestamp,
            $user->fresh()->email_verified_at->timestamp
        );
    }

    public function test_profile_email_must_be_unique(): void
    {
        $user = $this->createUser('owner@example.com');
        $this->createUser('taken@example.com');

        $this->withoutMiddleware(AuthToken::class);
        $this->actingAs($user);

        $this->patchJson('/api/profile', [
            'email' => 'taken@example.com',
        ])->assertUnprocessable();
    }

    public function test_user_can_upload_replace_and_remove_profile_picture(): void
    {
        Storage::fake('public');
        $user = $this->createUser('owner@example.com');

        $this->withoutMiddleware(AuthToken::class);
        $this->actingAs($user);

        $firstResponse = $this->post('/api/profile/picture', [
            'profile_picture' => $this->fakePng('first.png'),
        ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertStringContainsString(
            'profile-pictures/',
            $firstResponse->json('data.user.profile_picture_url')
        );
        $firstPath = $user->fresh()->profile_picture_path;
        Storage::disk('public')->assertExists($firstPath);

        $this->post('/api/profile/picture', [
            'profile_picture' => $this->fakePng('second.png'),
        ], ['Accept' => 'application/json'])->assertOk();

        $secondPath = $user->fresh()->profile_picture_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->deleteJson('/api/profile/picture')
            ->assertOk()
            ->assertJsonPath('data.user.profile_picture_url', null);

        $this->assertNull($user->fresh()->profile_picture_path);
        Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_user_can_soft_delete_account_and_revoke_authentication(): void
    {
        Storage::fake('public');
        $user = $this->createUser('owner@example.com');
        $picturePath = 'profile-pictures/avatar.png';
        Storage::disk('public')->put($picturePath, 'image-data');
        $user->profile_picture_path = $picturePath;
        $user->save();

        $plainToken = 'test-profile-delete-token';
        UserToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHour(),
        ]);

        $this->withToken($plainToken)
            ->deleteJson('/api/profile', [
                'current_password' => 'password',
            ])
            ->assertOk();

        $this->assertNotNull(User::withTrashed()->findOrFail($user->id)->deleted_at);
        $this->assertSame(0, UserToken::query()->where('user_id', $user->id)->count());
        Storage::disk('public')->assertMissing($picturePath);

        $this->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_account_deletion_requires_the_correct_current_password(): void
    {
        $user = $this->createUser('owner@example.com');
        $this->withoutMiddleware(AuthToken::class);
        $this->actingAs($user);

        $this->deleteJson('/api/profile', [
            'current_password' => 'wrong-password',
        ])->assertUnprocessable();

        $this->assertNull($user->fresh()->deleted_at);
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => 'user',
        ]);
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );
    }
}
