<?php

namespace Modules\Product\Features\CategoryManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    public function __construct(
        #[Required, Max(150)]
        public readonly string $name,

        public readonly ?int $parent_id = null,
        public readonly ?string $description = null,
        public readonly ?string $icon = null,
        public readonly bool $is_active = true,
        public readonly int $sort_order = 0,
    ) {}
}
