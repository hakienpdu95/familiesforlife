<?php

namespace Modules\KpiGoal\Queries;

use Illuminate\Database\Eloquent\Builder;
use Modules\KpiGoal\Models\KpiGoal;

class ListKpiGoalsHandler
{
    public function handle(ListKpiGoalsQuery $query): Builder
    {
        $builder = KpiGoal::with(['employee', 'approvedBy', 'snapshot'])
            ->orderBy('weight_percent', 'desc')
            ->orderBy('created_at', 'desc');

        if ($query->employee_id) {
            $builder->where('employee_id', $query->employee_id);
        }

        if ($query->cycle_label) {
            $builder->where('cycle_label', $query->cycle_label);
        }

        if ($query->status) {
            $builder->where('status', $query->status);
        }

        if ($query->goal_type) {
            $builder->where('goal_type', $query->goal_type);
        }

        return $builder;
    }
}
