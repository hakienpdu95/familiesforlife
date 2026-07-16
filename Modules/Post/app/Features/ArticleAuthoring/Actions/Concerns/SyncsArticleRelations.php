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
            // So sánh ép về string — $categoryId luôn là string (giá trị thô từ request), còn
            // $data->is_primary_category_id được Spatie Data cast thành int theo type-hint của
            // ArticleData. So sánh === giữa 2 kiểu khác nhau (vd "5" === 5) luôn false, khiến
            // is_primary không bao giờ được lưu đúng khi người dùng chủ động chọn danh mục chính.
            $sync[$categoryId] = ['is_primary' => (string) $categoryId === (string) $primaryId];
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
