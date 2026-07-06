<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OptionData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $criterion_id,

        #[Required, Max(1000)]
        public readonly string $label,

        #[Required]
        public readonly float $points,

        public readonly int $sort_order = 0,
    ) {}
}
