<?php

namespace Modules\Event\Features\EventCategoryManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class EventCategoryData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public readonly string $name,

        public readonly ?int $parent_id = null,
        public readonly ?string $icon = null,
        public readonly ?string $color_hex = null,
        public readonly bool $is_active = true,
        public readonly int $sort_order = 0,
    ) {}
}
