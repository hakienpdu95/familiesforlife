<?php

namespace Modules\Post\Policies;

use App\Models\User;
use Modules\Post\Models\PostBreakingNews;

/**
 * spec/Breaking_News_Ticker_Technical_Specification.md §6.3 — 1 permission duy nhất
 * (breaking_news.manage), gán cho platform_ops + platform_content_head (BreakingNewsPermissionSeeder).
 */
class PostBreakingNewsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('breaking_news.manage');
    }

    public function view(User $user, PostBreakingNews $breakingNews): bool
    {
        return $user->can('breaking_news.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('breaking_news.manage');
    }

    public function update(User $user, PostBreakingNews $breakingNews): bool
    {
        return $user->can('breaking_news.manage');
    }

    public function delete(User $user, PostBreakingNews $breakingNews): bool
    {
        return $user->can('breaking_news.manage');
    }
}
