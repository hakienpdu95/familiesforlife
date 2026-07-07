<?php

namespace Modules\Post\Policies;

use App\Models\User;
use Modules\Post\Enums\ArticleStatus;
use Modules\Post\Models\PostArticle;

class PostArticlePolicy
{
    /** Chỉ bài `published` nếu user không có quyền edit (docs/post-module-spec.md §10). */
    public function viewAny(User $user): bool
    {
        return $user->can('post_article.view');
    }

    public function view(User $user, PostArticle $postArticle): bool
    {
        if (! $user->can('post_article.view')) {
            return false;
        }

        return $postArticle->status === ArticleStatus::Published || $user->can('post_article.edit');
    }

    public function create(User $user): bool
    {
        return $user->can('post_article.create');
    }

    public function update(User $user, PostArticle $postArticle): bool
    {
        return $user->can('post_article.edit')
            && ($postArticle->created_by === $user->id || $user->can('post_article.publish'));
    }

    public function delete(User $user, PostArticle $postArticle): bool
    {
        return $user->can('post_article.delete')
            && ($postArticle->created_by === $user->id || $user->can('post_article.publish'));
    }

    public function submitForReview(User $user, PostArticle $postArticle): bool
    {
        return $this->update($user, $postArticle);
    }

    public function publish(User $user, PostArticle $postArticle): bool
    {
        return $user->can('post_article.publish');
    }

    public function schedule(User $user, PostArticle $postArticle): bool
    {
        return $user->can('post_article.publish');
    }

    public function archive(User $user, PostArticle $postArticle): bool
    {
        return $user->can('post_article.publish');
    }
}
