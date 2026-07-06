<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class CriterionData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $rubric_section_id,

        public readonly ?int $parent_id,

        #[Required, Max(20)]
        public readonly string $code,

        #[Required, Max(500)]
        public readonly string $label,

        #[Required]
        public readonly float $max_score,

        public readonly ?string $requirement_note = null,
        public readonly bool $is_scorable = false,
        public readonly int $sort_order = 0,
    ) {}
}
