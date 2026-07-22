<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryInterface;

class GetArticleRedirectClickStatsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $articleId,
        public readonly int $days = 30,
    ) {}
}
