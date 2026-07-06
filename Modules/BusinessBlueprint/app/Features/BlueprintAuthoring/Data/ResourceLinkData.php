<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ResourceLinkData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $blueprint_version_id,

        public readonly ?int $checklist_id = null,

        #[Required]
        public readonly string $resource_type,

        #[Required]
        public readonly int $resource_id,

        public readonly bool $is_required = false,
        public readonly int  $sort_order  = 0,
    ) {}
}
