<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ArticleStatus;
use Modules\Post\Models\PostArticle;

class ArchiveArticleAction
{
    use AsAction;

    public function handle(PostArticle $article): PostArticle
    {
        $article->update(['status' => ArticleStatus::Archived]);

        return $article;
    }
}
