<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ChecklistData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $phase_id,

        #[Required, Max(50)]
        public readonly string $code,

        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?string $description          = null,
        public readonly ?string $input_description      = null,
        public readonly ?string $action_description      = null,
        public readonly ?string $output_description      = null,
        public readonly bool    $required                 = true,
        public readonly string  $default_priority         = 'normal',
        public readonly ?float  $estimated_hours           = null,
        public readonly bool    $need_approval             = false,
        public readonly int     $sort_order                = 0,
        public readonly string  $status                    = 'active',
    ) {}
}
