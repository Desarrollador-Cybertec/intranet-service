<?php

namespace App\Policies;

use App\Models\ForumPost;
use App\Models\User;

class ForumPostPolicy
{
    /** Autor del post o admin. */
    public function update(User $user, ForumPost $post): bool
    {
        return $user->isAdmin() || $post->author_id === $user->id;
    }

    public function delete(User $user, ForumPost $post): bool
    {
        return $this->update($user, $post);
    }
}
