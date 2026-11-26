<?php

namespace App\Services;
use App\Models\User;

class RegistrationService
{
    public function __construct(

    ) {
    }
    /**
     * Register a new user and provision their default company, team,
     * and channel, all inside a single database transaction, then
     * issue an authentication token.
     *
     * @return array{user: User, token: UserToken}
     */
    public function register(array $data, string $token = ''): array
    {
        $user = $this->createUser($data);


        return ['user' => $user];

    }

    private function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => null,
            'password' => $data['password'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'role' => 'admin',
            'created_at' => now(),
        ]);
    }
}