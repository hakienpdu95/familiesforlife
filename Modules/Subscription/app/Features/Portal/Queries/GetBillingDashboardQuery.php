<?php

namespace Modules\Subscription\Features\Portal\Queries;

use App\Shared\Contracts\QueryInterface;

class GetBillingDashboardQuery implements QueryInterface
{
    public function __construct(
        public readonly int $organizationId,
    ) {}
}
