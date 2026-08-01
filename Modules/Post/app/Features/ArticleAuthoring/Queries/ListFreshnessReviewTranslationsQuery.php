<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryInterface;

class ListFreshnessReviewTranslationsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $staleAfterDays = 90,
        public readonly int $limit = 30,
    ) {}
}
