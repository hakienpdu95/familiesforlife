<?php

namespace Modules\Post\Policies;

use App\Models\User;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticleTranslation;

/**
 * Mọi method thao tác dữ liệu nhận PostArticleTranslation (không phải PostArticle) — xem
 * spec/PublishingEngine_Technical_Specification.md §8. Đăng ký cho CẢ 2 model class trong
 * PostServiceProvider (PostArticle::class cho viewAny/create; PostArticleTranslation::class
 * cho phần còn lại).
 */
class PostArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('post_article.view');
    }

    /** Chỉ bài `published` nếu user không có quyền edit. */
    public function view(User $user, PostArticleTranslation $translation): bool
    {
        if (! $user->can('post_article.view')) {
            return false;
        }

        return $translation->status === TranslationStatus::Published || $user->can('post_article.edit');
    }

    public function create(User $user): bool
    {
        return $user->can('post_article.create');
    }

    public function update(User $user, PostArticleTranslation $translation): bool
    {
        return $user->can('post_article.edit')
            && ($translation->article->created_by === $user->id || $user->can('post_article.publish'));
    }

    public function delete(User $user, PostArticleTranslation $translation): bool
    {
        return $user->can('post_article.delete')
            && ($translation->article->created_by === $user->id || $user->can('post_article.publish'));
    }

    public function submitForReview(User $user, PostArticleTranslation $translation): bool
    {
        return $this->update($user, $translation);
    }

    public function approve(User $user, PostArticleTranslation $translation): bool
    {
        return $user->can('post_article.publish');
    }

    public function publish(User $user, PostArticleTranslation $translation): bool
    {
        return $user->can('post_article.publish');
    }

    public function schedule(User $user, PostArticleTranslation $translation): bool
    {
        return $user->can('post_article.publish');
    }

    public function archive(User $user, PostArticleTranslation $translation): bool
    {
        return $user->can('post_article.publish');
    }

    public function unpublish(User $user, PostArticleTranslation $translation): bool
    {
        return $user->can('post_article.unpublish');
    }
}
