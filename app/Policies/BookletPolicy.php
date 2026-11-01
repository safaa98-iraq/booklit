<?php

namespace App\Policies;

use App\Models\Booklet;
use App\Models\User;

class BookletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function view(User $user, Booklet $booklet): bool
    {
        if ($user->isEditor()) {
            return $user->canManageSubject($booklet->subject_id);
        }

        return $user->isStudent()
            && $booklet->is_published
            && $booklet->subject?->is_active;
    }

    /** Editors can create booklets only if at least one subject is assigned to them. */
    public function create(User $user): bool
    {
        return $user->isEditor() && $user->assignedSubjects()->exists();
    }

    public function update(User $user, Booklet $booklet): bool
    {
        return $user->isEditor() && $user->canManageSubject($booklet->subject_id);
    }

    public function delete(User $user, Booklet $booklet): bool
    {
        return $this->update($user, $booklet);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isEditor();
    }
}
