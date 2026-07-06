<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class WorkflowData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $blueprint_version_id,

        public readonly ?int $capability_id = null,

        #[Required, Max(50)]
        public readonly string $code,

        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?string $description = null,
        public readonly int     $sort_order   = 0,
        public readonly string  $status       = 'active',
    ) {}
}
