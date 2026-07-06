<?php

namespace Modules\Subscription\Features\Analytics\Queries;

use App\Shared\Contracts\QueryInterface;

class GetSubscriptionAnalyticsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $year,
        public readonly int $month,
    ) {}
}
