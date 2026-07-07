<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\SyncsArticleRelations;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Models\PostArticle;

class UpdateArticleAction
{
    use AsAction;
    use SyncsArticleRelations;

    public function __construct(
        private readonly SyncContentBlocksAction $syncContentBlocks,
    ) {}

    /** Slug giữ nguyên sau khi tạo (tránh vỡ link đã chia sẻ), kể cả khi đổi tiêu đề. */
    public function handle(PostArticle $article, ArticleData $data): PostArticle
    {
        return DB::transaction(function () use ($article, $data) {
            $article->update([
                'title'            => $data->title,
                'excerpt'          => $data->excerpt,
                'format'           => $data->format,
                'cover_image_url'  => $data->cover_image_url,
                'seo_title'        => $data->seo_title,
                'seo_description'  => $data->seo_description,
                'is_featured'      => $data->is_featured,
                'updated_by'       => auth()->id(),
            ]);

            $this->syncCategories($article, $data);
            $this->syncTags($article, $data);
            $this->syncContentBlocks->handle($article, $data->blocks);

            return $article;
        });
    }
}
