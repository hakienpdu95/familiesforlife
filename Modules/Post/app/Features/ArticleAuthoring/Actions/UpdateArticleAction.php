<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\SyncsArticleRelations;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Models\PostArticle;

/** Chỉ cập nhật field cấp PostArticle (dùng chung mọi ngôn ngữ) — title/excerpt/seo_title/seo_description/blocks xem UpdateTranslationAction. */
class UpdateArticleAction
{
    use AsAction;
    use SyncsArticleRelations;

    public function handle(PostArticle $article, ArticleData $data): PostArticle
    {
        return DB::transaction(function () use ($article, $data) {
            $article->update([
                'format'          => $data->format,
                'cover_image_url' => $data->cover_image_url,
                'is_featured'     => $data->is_featured,
                'updated_by'      => auth()->id(),
            ]);

            $this->syncCategories($article, $data);
            $this->syncTags($article, $data);

            return $article;
        });
    }
}
