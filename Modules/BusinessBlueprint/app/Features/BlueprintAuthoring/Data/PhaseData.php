<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class PhaseData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $workflow_id,

        #[Required, Max(50)]
        public readonly string $code,

        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?string $description                 = null,
        public readonly int     $sort_order                    = 0,
        public readonly ?string $entry_condition                = null,
        public readonly ?string $exit_condition                 = null,
        public readonly bool    $is_initial                     = false,
        public readonly bool    $auto_assign_data_collection    = false,
        public readonly string  $status                         = 'active',
    ) {}
}
