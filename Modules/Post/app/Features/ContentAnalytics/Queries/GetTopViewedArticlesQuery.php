<?php

namespace Modules\Post\Features\ContentAnalytics\Queries;

use App\Shared\Contracts\QueryInterface;

class GetTopViewedArticlesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $limit = 10,
    ) {}
}
