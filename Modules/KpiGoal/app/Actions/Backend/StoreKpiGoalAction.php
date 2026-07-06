<?php

namespace Modules\KpiGoal\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KpiGoal\Data\Requests\StoreKpiGoalData;
use Modules\KpiGoal\Models\KpiGoal;

class StoreKpiGoalAction
{
    use AsAction;

    public function handle(StoreKpiGoalData $data): KpiGoal
    {
        return KpiGoal::create([
            'uuid'           => Str::uuid(),
            'organization_id' => $data->organization_id,
            'employee_id'    => $data->employee_id,
            'cycle_label'    => $data->cycle_label,
            'cycle_start'    => $data->cycle_start,
            'cycle_end'      => $data->cycle_end,
            'parent_goal_id' => $data->parent_goal_id,
            'title'          => $data->title,
            'description'    => $data->description,
            'goal_type'      => $data->goal_type->value,
            'target_value'   => $data->target_value,
            'current_value'  => 0,
            'unit'           => $data->unit,
            'direction'      => $data->direction->value,
            'achievement_pct'=> 0,
            'weight_percent' => $data->weight_percent,
            'status'         => 'draft',
            'created_by'     => auth()->id(),
        ]);
    }
}
