<?php

namespace App\Support\Presence;

use App\Models\User;

/**
 * Stand-in until real connection tracking exists: every user counts as connected,
 * so presence follows the status the user chose.
 */
class AlwaysConnectedTracker implements ConnectionTracker
{
    public function isConnected(User $user): bool
    {

        return true;
    }
}