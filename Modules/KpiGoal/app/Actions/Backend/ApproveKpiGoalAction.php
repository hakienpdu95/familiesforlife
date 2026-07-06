<?php

namespace Modules\KpiGoal\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Models\Employee;
use Modules\KpiGoal\Enums\KpiGoalStatus;
use Modules\KpiGoal\Models\KpiGoal;

class ApproveKpiGoalAction
{
    use AsAction;

    public function handle(KpiGoal $goal, Employee $approver): KpiGoal
    {
        if (!$goal->isDraft()) {
            throw new \RuntimeException('Chỉ có thể duyệt mục tiêu ở trạng thái bản nháp.');
        }

        // Sum of weight_percent for active+completed goals in same cycle/employee (excluding current)
        $existingWeight = KpiGoal::where('employee_id', $goal->employee_id)
            ->where('cycle_label', $goal->cycle_label)
            ->whereIn('status', KpiGoalStatus::weightedStatuses())
            ->where('id', '!=', $goal->id)
            ->sum('weight_percent');

        $totalWeight = $existingWeight + $goal->weight_percent;

        if ($totalWeight !== 100) {
            throw new \RuntimeException(
                "Tổng trọng số phải bằng 100%. Hiện tại: {$totalWeight}% "
                . "(các mục tiêu khác: {$existingWeight}% + mục tiêu này: {$goal->weight_percent}%)"
            );
        }

        $goal->update([
            'status'      => KpiGoalStatus::Active->value,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return $goal->fresh();
    }
}
