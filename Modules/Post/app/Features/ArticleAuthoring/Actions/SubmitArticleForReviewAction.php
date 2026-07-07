<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ArticleStatus;
use Modules\Post\Models\PostArticle;

class SubmitArticleForReviewAction
{
    use AsAction;

    public function handle(PostArticle $article): PostArticle
    {
        $article->update(['status' => ArticleStatus::PendingReview]);

        return $article;
    }
}
