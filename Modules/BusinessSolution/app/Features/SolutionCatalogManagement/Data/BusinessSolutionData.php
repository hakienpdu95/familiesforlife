<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class BusinessSolutionData extends Data
{
    public function __construct(
        #[Required, Max(50), Regex('/^[A-Z0-9\-]+$/')]
        public readonly string $code,

        #[Required, Max(255)]
        public readonly string $name,

        #[Required]
        public readonly int $vertical_id,

        public readonly ?string $short_description = null,
        public readonly ?string $description        = null,

        /** @var array<int, string>|null */
        public readonly ?array $target_customers = null,

        public readonly string  $visibility     = 'private',
        public readonly ?string $thumbnail_url  = null,
    ) {}
}
