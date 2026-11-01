<?php

namespace App\Policies;

use App\Models\Stage;
use App\Models\User;

/** Stages are managed by admins only (admins pass via Gate::before). */
class StagePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Stage $stage): bool
    {
        return $user->isStudent() && $stage->is_active;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Stage $stage): bool
    {
        return false;
    }

    public function delete(User $user, Stage $stage): bool
    {
        return false;
    }
}
