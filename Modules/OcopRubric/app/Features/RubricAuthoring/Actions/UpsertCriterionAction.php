<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Features\RubricAuthoring\Data\CriterionData;
use Modules\OcopRubric\Models\OcopRubricCriterion;

/**
 * Tạo mới hoặc sửa 1 node trong cây tiêu chí — luôn tự tính lại `path`/`depth`
 * theo cha hiện tại (materialized path, cùng pattern Branch: path chứa chuỗi id
 * tổ tiên, không gồm chính node). Khi node bị DI CHUYỂN sang cha khác, toàn bộ
 * cây con phải được tính lại theo cha mới — không tự sinh sai lệch ngầm.
 */
class UpsertCriterionAction
{
    use AsAction;

    public function handle(CriterionData $data, ?OcopRubricCriterion $criterion = null): OcopRubricCriterion
    {
        return DB::transaction(function () use ($data, $criterion) {
            $parent = $data->parent_id ? OcopRubricCriterion::findOrFail($data->parent_id) : null;

            $attributes = [
                'rubric_section_id' => $data->rubric_section_id,
                'parent_id'         => $data->parent_id,
                'path'              => $parent ? $parent->path . $parent->id . '/' : '/',
                'depth'             => $parent ? $parent->depth + 1 : 0,
                'code'              => $data->code,
                'label'             => $data->label,
                'max_score'         => $data->max_score,
                'requirement_note'  => $data->requirement_note,
                'is_scorable'       => $data->is_scorable,
                'sort_order'        => $data->sort_order,
            ];

            if (!$criterion) {
                return OcopRubricCriterion::create($attributes);
            }

            $movedParent = $criterion->parent_id !== $data->parent_id;
            $criterion->update($attributes);

            if ($movedParent) {
                $this->recalculateDescendants($criterion->fresh());
            }

            return $criterion->fresh();
        });
    }

    private function recalculateDescendants(OcopRubricCriterion $node): void
    {
        foreach ($node->children as $child) {
            $child->update([
                'path'  => $node->path . $node->id . '/',
                'depth' => $node->depth + 1,
            ]);
            $this->recalculateDescendants($child->fresh());
        }
    }
}
