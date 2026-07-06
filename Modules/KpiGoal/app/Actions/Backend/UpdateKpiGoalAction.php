<?php

namespace Modules\KpiGoal\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KpiGoal\Data\Requests\UpdateKpiGoalData;
use Modules\KpiGoal\Models\KpiGoal;

class UpdateKpiGoalAction
{
    use AsAction;

    public function handle(KpiGoal $goal, UpdateKpiGoalData $data): KpiGoal
    {
        if (!$goal->isEditable()) {
            throw new \RuntimeException('Chỉ có thể sửa mục tiêu ở trạng thái bản nháp hoặc đang theo dõi.');
        }

        $goal->update([
            'title'          => $data->title,
            'target_value'   => $data->target_value,
            'direction'      => $data->direction->value,
            'weight_percent' => $data->weight_percent,
            'cycle_label'    => $data->cycle_label,
            'cycle_start'    => $data->cycle_start,
            'cycle_end'      => $data->cycle_end,
            'parent_goal_id' => $data->parent_goal_id,
            'description'    => $data->description,
            'unit'           => $data->unit,
        ]);

        return $goal->fresh();
    }
}
