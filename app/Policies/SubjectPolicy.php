<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

/**
 * Subjects are created and assigned by admins. Editors may only see the
 * subjects assigned to them; students may see any active subject (whether
 * its content is unlocked is decided by SubscriptionService).
 */
class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Subject $subject): bool
    {
        if ($user->isEditor()) {
            return $user->canManageSubject($subject);
        }

        return $user->isStudent() && $subject->is_active && (bool) $subject->stage?->is_active;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Subject $subject): bool
    {
        return false;
    }

    public function delete(User $user, Subject $subject): bool
    {
        return false;
    }
}
