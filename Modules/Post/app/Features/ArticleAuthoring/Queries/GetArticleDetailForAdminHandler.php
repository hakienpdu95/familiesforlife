<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Post\Models\PostArticle;

class GetArticleDetailForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): PostArticle
    {
        /** @var GetArticleDetailForAdminQuery $query */
        return PostArticle::with(['categories', 'tags', 'translations'])->findOrFail($query->articleId);
    }
}
