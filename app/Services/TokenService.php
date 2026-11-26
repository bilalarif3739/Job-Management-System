<?php

namespace App\Services;
use App\Models\User;
use App\Models\UserToken;

class TokenService
{
    /**
     * Issue a new random, unpredictable authentication token for a user.
     */
    public function issueTokenFor(): string
    {

        return UserToken::generatePlainTextToken();

    }

    public function encryptToken(User $user, string $token): void
    {
        UserToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(1440),

        ]);
    }
    public function revoke(string $token): void
    {
        UserToken::where('token_hash', hash('sha256', $token))->delete();
    }
}
