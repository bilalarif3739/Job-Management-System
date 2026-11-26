<?php

namespace App\Services;
use App\Models\User;

class RegistrationService
{
    /**
     * @return array{user: User}
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