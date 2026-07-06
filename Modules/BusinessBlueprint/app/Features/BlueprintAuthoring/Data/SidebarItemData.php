<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class SidebarItemData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $blueprint_version_id,

        public readonly ?int $parent_id = null,

        #[Required, Max(100)]
        public readonly string $module_key,

        #[Required, Max(255)]
        public readonly string $label,

        public readonly ?string $icon       = null,
        public readonly int     $sort_order = 0,
    ) {}
}
