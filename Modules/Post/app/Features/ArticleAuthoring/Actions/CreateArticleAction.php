<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ArticleStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\SyncsArticleRelations;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Models\PostArticle;

class CreateArticleAction
{
    use AsAction;
    use SyncsArticleRelations;

    public function __construct(
        private readonly SyncContentBlocksAction $syncContentBlocks,
    ) {}

    public function handle(ArticleData $data): PostArticle
    {
        return DB::transaction(function () use ($data) {
            $article = PostArticle::create([
                'title'            => $data->title,
                'slug'             => $this->uniqueSlug($data->title),
                'excerpt'          => $data->excerpt,
                'format'           => $data->format,
                'status'           => ArticleStatus::Draft,
                'cover_image_url'  => $data->cover_image_url,
                'seo_title'        => $data->seo_title,
                'seo_description'  => $data->seo_description,
                'is_featured'      => $data->is_featured,
                'created_by'       => auth()->id(),
            ]);

            $this->syncCategories($article, $data);
            $this->syncTags($article, $data);
            $this->syncContentBlocks->handle($article, $data->blocks);

            return $article;
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 2;

        while (PostArticle::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
