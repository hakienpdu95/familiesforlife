<?php

namespace Modules\EntityComparison\Features\CriterionManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Models\Criterion;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §9 — soft delete, giữ nguyên criterion_values
 * đã có (không cascade xóa dữ liệu lịch sử) — trang so sánh cũ vẫn đọc được qua withTrashed()
 * (BuildComparisonTableAction), chỉ ẩn khỏi form nhập mới/trang filter.
 */
class DeleteCriterionAction
{
    use AsAction;

    public function handle(Criterion $criterion): void
    {
        $criterion->delete();
    }
}
