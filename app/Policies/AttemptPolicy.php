<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;

class AttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Attempt $attempt): bool
    {
        return $user->getKey() === $attempt->user_id;
    }

    /** Saving answers / submitting: owner only. */
    public function update(User $user, Attempt $attempt): bool
    {
        return $user->isStudent() && $user->getKey() === $attempt->user_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Attempt $attempt): bool
    {
        return false;
    }
}
