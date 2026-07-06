<?php

namespace Modules\LeadPipelineStage\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateStageData extends Data
{
    public function __construct(
        #[Required, StringType, Max(64)]
        public readonly string $label,

        #[Required, StringType, Max(16)]
        public readonly string $color,

        #[Required, Integer, Min(0), Max(255)]
        public readonly int $sort_order,

        #[Required, Integer, Min(0), Max(100)]
        public readonly int $probability,

        #[Nullable, BooleanType]
        public readonly bool $is_won = false,

        #[Nullable, BooleanType]
        public readonly bool $is_lost = false,
    ) {}
}
