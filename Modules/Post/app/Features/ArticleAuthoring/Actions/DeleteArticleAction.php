<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticle;

class DeleteArticleAction
{
    use AsAction;

    public function handle(PostArticle $article): void
    {
        $article->delete();
    }
}
