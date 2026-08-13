<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Models\EntityType;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §9 — soft delete. Không cascade xóa Entity con —
 * business rule "không tạo Entity MỚI thuộc type đã xóa mềm" enforce ở CreateEntityAction, các
 * Entity đã có tiếp tục tồn tại (soft delete EntityType không phá dữ liệu lịch sử).
 */
class DeleteEntityTypeAction
{
    use AsAction;

    public function handle(EntityType $entityType): void
    {
        $entityType->delete();
    }
}
