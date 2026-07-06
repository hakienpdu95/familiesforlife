<?php

namespace Modules\Subscription\Features\Plans\Data;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class PlanData extends Data
{
    public function __construct(
        #[Required] public readonly string  $slug,
        #[Required] public readonly string  $name,
        public readonly ?string  $description        = null,
        #[Numeric, Min(0)] public readonly float $price = 0.0,
        public readonly ?float   $annual_price       = null,
        public readonly string   $currency           = 'VND',
        public readonly string   $invoice_interval   = 'month',
        public readonly int      $invoice_period     = 1,
        public readonly int      $trial_period       = 0,
        public readonly string   $trial_interval     = 'day',
        public readonly int      $grace_period       = 3,
        public readonly string   $grace_interval     = 'day',
        public readonly bool     $is_active          = true,
        public readonly bool     $is_public          = true,
        public readonly string   $tier               = 'growth',
        public readonly ?string  $tag_line           = null,
        public readonly ?string  $badge_color        = null,
    ) {}
}
