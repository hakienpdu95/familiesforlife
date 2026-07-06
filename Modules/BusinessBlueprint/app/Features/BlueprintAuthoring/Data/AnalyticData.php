<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AnalyticData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $blueprint_version_id,

        #[Required, Max(100)]
        public readonly string $metric_code,

        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?string $description  = null,
        public readonly ?string $metric_type    = null,
        public readonly ?string $formula         = null,
        public readonly ?string $source_type     = null,
        public readonly string  $status          = 'active',
    ) {}
}
