<?php

namespace Modules\Subscription\Features\Plans\Data;

use Spatie\LaravelData\Data;

class PlanFeatureData extends Data
{
    public function __construct(
        public readonly string  $slug,
        public readonly string  $name,
        public readonly string  $value,
        public readonly ?int    $resettable_period   = null,
        public readonly ?string $resettable_interval = null,
    ) {}
}
