<?php

namespace App\Support\Presence;

use App\Models\User;

/**
 * Whether the user has a live connection right now.
 * Owned by Bilal and Waleed (Reverb); StatusService only asks the question.
 */
interface ConnectionTracker
{
    public function isConnected(User $user): bool;
}

