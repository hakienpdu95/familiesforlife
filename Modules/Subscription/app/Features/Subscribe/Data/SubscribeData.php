<?php

namespace Modules\Subscription\Features\Subscribe\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class SubscribeData extends Data
{
    public function __construct(
        public readonly int     $planId,
        public readonly ?string $slug          = null,
        public readonly ?string $idempotentKey = null,
        public readonly ?Carbon $startDate     = null,
    ) {}
}
