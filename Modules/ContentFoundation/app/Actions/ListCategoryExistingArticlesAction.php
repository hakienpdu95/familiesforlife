<?php

namespace Modules\ContentFoundation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12.8 — tách từ CoreIdeaExtractor. Tự động kéo tiêu đề bài ĐÃ PUBLISH
 * trong 1 category vào prompt để tránh AI đề xuất trùng ý tưởng đã viết. Lấy `mainTranslation()`
 * của mỗi bài (helper PHP có sẵn trên PostArticle, dùng collection `translations` đã eager-load) —
 * KHÔNG viết join/query riêng, tránh trùng logic xác định "bản dịch chính" của 1 bài với chỗ khác.
 */
class ListCategoryExistingArticlesAction
{
    use AsAction;

    /** @return string[] Tiêu đề bài đã publish, mới nhất trước, tối đa `existing_articles.max_titles`. */
    public function handle(PostCategory $category): array
    {
        $maxTitles    = (int) config('content_foundation.existing_articles.max_titles', 30);
        $dbFetchLimit = (int) config('content_foundation.existing_articles.db_fetch_limit', 100);

        return $category->articles()
            ->with('translations')
            ->latest('post_articles.created_at')
            ->limit($dbFetchLimit)
            ->get()
            ->map(static fn (PostArticle $article): ?PostArticleTranslation => $article->mainTranslation())
            ->filter(static fn (?PostArticleTranslation $t): bool => $t !== null && $t->status === TranslationStatus::Published)
            ->sortByDesc(static fn (PostArticleTranslation $t) => $t->published_at)
            ->take($maxTitles)
            ->pluck('title')
            ->values()
            ->all();
    }
}
