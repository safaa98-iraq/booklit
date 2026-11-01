<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function view(User $user, Topic $topic): bool
    {
        return $user->isEditor() && $user->canManageSubject($topic->booklet->subject_id);
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Topic $topic): bool
    {
        return $this->view($user, $topic);
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $this->view($user, $topic);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function reorder(User $user): bool
    {
        return $user->isEditor();
    }
}
