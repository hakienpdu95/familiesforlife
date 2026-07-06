<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Models\OcopRubricCriterion;

/**
 * Sắp lại thứ tự hiển thị của các tiêu chí CÙNG 1 cha (kéo-thả trên UI cây).
 * Không đổi parent_id/path — chỉ đổi sort_order theo đúng thứ tự mảng truyền vào.
 */
class ReorderCriteriaAction
{
    use AsAction;

    /** @param int[] $orderedCriterionIds */
    public function handle(array $orderedCriterionIds): void
    {
        DB::transaction(function () use ($orderedCriterionIds) {
            foreach ($orderedCriterionIds as $index => $id) {
                OcopRubricCriterion::whereKey($id)->update(['sort_order' => $index]);
            }
        });
    }
}
