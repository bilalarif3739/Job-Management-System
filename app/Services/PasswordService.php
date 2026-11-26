<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserToken;

class PasswordService
{
    /**
     * Change the password (hashed by the model cast), record when, and revoke every
     * other token of this user while keeping the one making the request.
     */
    public function change(User $user, string $newPassword): void
    {
        $user->password = $newPassword;
        $user->password_changed_at = now();
        $user->save();

        $currentToken = request()->attributes->get('current_token') ?? request()->bearerToken();

        if ($currentToken) {
            $currentHash = hash('sha256', $currentToken);
            UserToken::where('user_id', (string) $user->id)
                ->where('token_hash', '!=', $currentHash)
                ->delete();
        }
    }
}

