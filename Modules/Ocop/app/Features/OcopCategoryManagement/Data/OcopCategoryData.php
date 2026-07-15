<?php

namespace Modules\Ocop\Features\OcopCategoryManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** Validate thật nằm ở OcopCategoryAdminController::validated() — DTO chỉ hydrate. */
class OcopCategoryData extends Data
{
    public function __construct(
        #[Required, Max(150)]
        public readonly string $name,

        public readonly ?string $icon = null,
        public readonly bool $is_active = true,
        public readonly int $sort_order = 0,
    ) {}
}
