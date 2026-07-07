<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryInterface;

class GetArticleDetailForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly int $articleId,
    ) {}
}
