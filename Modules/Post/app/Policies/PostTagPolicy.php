<?php

namespace Modules\Post\Policies;

use App\Models\User;
use Modules\Post\Models\PostTag;

class PostTagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('post_article.view');
    }

    public function view(User $user, PostTag $postTag): bool
    {
        return $user->can('post_article.view');
    }

    public function create(User $user): bool
    {
        return $user->can('post_tag.manage');
    }

    public function update(User $user, PostTag $postTag): bool
    {
        return $user->can('post_tag.manage');
    }

    public function delete(User $user, PostTag $postTag): bool
    {
        return $user->can('post_tag.manage');
    }
}
