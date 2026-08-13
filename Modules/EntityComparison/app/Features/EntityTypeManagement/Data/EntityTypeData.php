<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Data;

use Spatie\LaravelData\Data;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §0 mục 4 — validate thật nằm ở
 * EntityTypeAdminController/FormRequest, DTO chỉ hydrate, đúng nguyên tắc OcopProductData.
 */
class EntityTypeData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $icon = null,
        public readonly bool $is_active = true,
        public readonly int $sort_order = 0,
    ) {}
}
