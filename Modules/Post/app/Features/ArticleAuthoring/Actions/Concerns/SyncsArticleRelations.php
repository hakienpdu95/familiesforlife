<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions\Concerns;

use Illuminate\Support\Str;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostTag;

trait SyncsArticleRelations
{
    private function syncCategories(PostArticle $article, ArticleData $data): void
    {
        $categoryIds = $data->category_ids;
        $primaryId   = $data->is_primary_category_id ?? ($categoryIds[0] ?? null);

        $sync = [];
        foreach ($categoryIds as $categoryId) {
            $sync[$categoryId] = ['is_primary' => $categoryId === $primaryId];
        }

        $article->categories()->sync($sync);
    }

    private function syncTags(PostArticle $article, ArticleData $data): void
    {
        $names = collect(explode(',', (string) $data->tags))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique();

        $tagIds = $names->map(function (string $name) {
            $tag = PostTag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );

            return $tag->id;
        });

        $article->tags()->sync($tagIds);
    }

    /**
     * spec/Province_Showcase_Technical_Specification.md §3.4.1 — chỉ gọi từ UpdateArticleAction
     * (form sửa bài viết), KHÔNG gọi từ CreateArticleAction (create form không có multi-select
     * này) — không bắt buộc, bài viết không gắn OCOP nào vẫn publish bình thường.
     */
    private function syncOcopProducts(PostArticle $article, ArticleData $data): void
    {
        $article->ocopProducts()->sync($data->ocop_product_ids);
    }
}
