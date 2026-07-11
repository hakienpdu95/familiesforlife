<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\SyncsArticleRelations;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Models\PostArticle;

/** Chỉ tạo "vỏ" PostArticle (chưa có translation nào) — nội dung/title tạo qua CreateTranslationAction ngay sau, trên trang edit. */
class CreateArticleAction
{
    use AsAction;
    use SyncsArticleRelations;

    public function handle(ArticleData $data): PostArticle
    {
        return DB::transaction(function () use ($data) {
            $article = PostArticle::create([
                'main_locale'     => $data->main_locale ?: config('post.default_locale'),
                'format'          => $data->format,
                'cover_image_url' => $data->cover_image_url,
                'is_featured'     => $data->is_featured,
                'created_by'      => auth()->id(),
            ]);

            $this->syncCategories($article, $data);
            $this->syncTags($article, $data);

            return $article;
        });
    }
}
