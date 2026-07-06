<?php

namespace Modules\Subscription\Features\Subscribe\Queries;

use App\Shared\Contracts\QueryInterface;

class GetActiveSubscriptionQuery implements QueryInterface
{
    public function __construct(
        public readonly int $organizationId,
    ) {}
}
