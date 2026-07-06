<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class DeploymentRoleData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $blueprint_version_id,

        #[Required, Max(100)]
        public readonly string $role_code,

        #[Required, Max(255)]
        public readonly string $role_name,

        public readonly ?string $description = null,
        public readonly int     $sort_order   = 0,
    ) {}
}
