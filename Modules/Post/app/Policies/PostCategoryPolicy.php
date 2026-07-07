<?php

namespace Modules\Post\Policies;

use App\Models\User;
use Modules\Post\Models\PostCategory;

class PostCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('post_article.view');
    }

    public function view(User $user, PostCategory $postCategory): bool
    {
        return $user->can('post_article.view');
    }

    public function create(User $user): bool
    {
        return $user->can('post_category.manage');
    }

    public function update(User $user, PostCategory $postCategory): bool
    {
        return $user->can('post_category.manage');
    }

    public function delete(User $user, PostCategory $postCategory): bool
    {
        return $user->can('post_category.manage');
    }
}
