<?php

namespace Modules\Subscription\Features\Plans\Queries;

use App\Shared\Contracts\QueryInterface;

class GetPlanQuery implements QueryInterface
{
    public function __construct(
        public readonly int  $planId,
        public readonly bool $withFeatures = true,
    ) {}
}
