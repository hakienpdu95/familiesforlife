<?php

namespace Modules\EntityComparison\Features\EntityManagement\Data;

use Spatie\LaravelData\Data;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §0 mục 4 — validate thật nằm ở
 * EntityAdminController/FormRequest, DTO chỉ hydrate, đúng nguyên tắc OcopProductData.
 *
 * Giá trị tiêu chí (criterion_values) KHÔNG nằm trong DTO này — hình dạng của nó phụ thuộc động
 * vào các Criterion đã gán cho entity_type_id (khác nhau mỗi EntityType), không khớp 1 DTO có
 * property cố định. SetCriterionValuesAction nhận mảng criterion_id => value riêng, thẳng từ
 * request đã validate — xem EntityAdminController.
 */
class EntityData extends Data
{
    public function __construct(
        public readonly int $entity_type_id,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $is_active = true,
        public readonly int $sort_order = 0,
    ) {}
}
