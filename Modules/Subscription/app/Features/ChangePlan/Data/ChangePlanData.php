<?php

namespace Modules\Subscription\Features\ChangePlan\Data;

use Spatie\LaravelData\Data;

class ChangePlanData extends Data
{
    public function __construct(
        public readonly int     $newPlanId,
        public readonly ?string $reason = null,
    ) {}
}
