<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OutcomeData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $blueprint_version_id,

        #[Required, Max(50)]
        public readonly string $code,

        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?string $description     = null,
        public readonly ?string $success_metric  = null,
        public readonly int     $sort_order       = 0,
        public readonly string  $status           = 'active',
    ) {}
}
