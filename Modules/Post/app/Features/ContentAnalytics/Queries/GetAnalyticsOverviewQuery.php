<?php

namespace Modules\Post\Features\ContentAnalytics\Queries;

use App\Shared\Contracts\QueryInterface;

class GetAnalyticsOverviewQuery implements QueryInterface
{
    public function __construct(
        public readonly int $days = 30,
    ) {}
}
