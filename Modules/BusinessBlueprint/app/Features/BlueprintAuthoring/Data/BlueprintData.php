<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class BlueprintData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $business_solution_id,

        #[Required, Max(50), Regex('/^[A-Z0-9\-]+$/')]
        public readonly string $code,

        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?string $description = null,
    ) {}
}
