<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function view(User $user, Question $question): bool
    {
        return $user->isEditor() && $user->canManageSubject($question->subject_id);
    }

    public function create(User $user): bool
    {
        return $user->isEditor() && $user->assignedSubjects()->exists();
    }

    public function update(User $user, Question $question): bool
    {
        return $this->view($user, $question);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->view($user, $question);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function replicate(User $user, Question $question): bool
    {
        return $this->view($user, $question);
    }
}
