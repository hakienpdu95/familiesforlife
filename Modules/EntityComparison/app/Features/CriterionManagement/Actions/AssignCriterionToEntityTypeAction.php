<?php

namespace Modules\EntityComparison\Features\CriterionManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Models\EntityType;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §7.1 mục 3 — CHỈ cập nhật is_required/
 * sort_order cho Criteria ĐÃ thuộc EntityType này. KHÔNG add/remove membership qua đây — 1
 * Criterion chỉ thuộc đúng 1 EntityType, quyết định ở entity_type_id bắt buộc của
 * CreateCriterionAction/UpdateCriterionAction (luôn sync về đúng 1 giá trị). Dùng sync() từ phía
 * EntityType ở đây sẽ có thể gán thêm 1 Criterion đang thuộc type khác vào type này, phá vỡ
 * ràng buộc 1-1 đó — nên chỉ updateExistingPivot() cho các id đã có sẵn.
 */
class AssignCriterionToEntityTypeAction
{
    use AsAction;

    /**
     * @param  array<int, bool>  $isRequired  keyed by criterion_id
     * @param  array<int, int>  $sortOrder  keyed by criterion_id
     */
    public function handle(EntityType $entityType, array $isRequired, array $sortOrder): void
    {
        foreach ($entityType->criteria()->get() as $criterion) {
            $entityType->criteria()->updateExistingPivot($criterion->id, [
                'is_required' => (bool) ($isRequired[$criterion->id] ?? false),
                'sort_order' => (int) ($sortOrder[$criterion->id] ?? 0),
            ]);
        }
    }
}
