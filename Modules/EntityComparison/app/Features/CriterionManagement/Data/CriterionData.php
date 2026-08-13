<?php

namespace Modules\EntityComparison\Features\CriterionManagement\Data;

use Spatie\LaravelData\Data;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §0 mục 4 — validate thật nằm ở
 * CriterionAdminController/FormRequest, DTO chỉ hydrate, đúng nguyên tắc OcopProductData.
 *
 * `options` chỉ có ý nghĩa khi `type` ∈ {select, multi_select} (CriterionType::hasOptions()) —
 * mảng ['value' => string, 'label' => string], Action sync vào bảng con criterion_options (§0
 * mục 8 — KHÔNG lưu JSON).
 */
class CriterionData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        /** 1 criterion — 1 entity type, bắt buộc, gán ngay khi tạo/sửa. */
        public readonly int $entity_type_id,
        public readonly ?string $unit = null,
        public readonly ?string $description = null,
        public readonly bool $is_filterable = true,
        public readonly bool $is_comparable = true,
        public readonly int $sort_order = 0,
        /** @var array<int, array{value: string, label: string}> */
        public readonly array $options = [],
    ) {}
}
