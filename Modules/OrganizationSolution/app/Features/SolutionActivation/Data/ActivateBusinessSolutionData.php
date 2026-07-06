<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ActivateBusinessSolutionData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $business_solution_id,

        #[Required]
        public readonly int $blueprint_version_id,

        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?int $owner_id = null,
    ) {}
}
