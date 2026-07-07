<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ArticleStatus;
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;
use Modules\Post\Models\PostArticle;

class PublishArticleAction
{
    use AsAction;

    public function handle(PostArticle $article): PostArticle
    {
        $article->update([
            'status'       => ArticleStatus::Published,
            'published_at' => $article->published_at ?? now(),
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);

        event(new ArticlePublished($article));

        return $article;
    }
}
