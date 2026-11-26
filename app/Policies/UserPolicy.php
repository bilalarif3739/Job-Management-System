<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $authUser, User $target): bool
    {
        return true;
    }

    /** Users may only modify themselves. */
    public function update(User $authUser, User $target): bool
    {
        return (string) $authUser->getKey() === (string) $target->getKey();
    }
}

