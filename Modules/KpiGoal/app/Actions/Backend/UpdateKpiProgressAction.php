<?php

namespace Modules\KpiGoal\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KpiGoal\Enums\KpiGoalStatus;
use Modules\KpiGoal\Models\KpiGoal;

class UpdateKpiProgressAction
{
    use AsAction;

    public function handle(KpiGoal $goal, float $currentValue): KpiGoal
    {
        if (!$goal->isActive()) {
            throw new \RuntimeException('Chỉ có thể cập nhật tiến độ cho mục tiêu đang theo dõi.');
        }

        // Updating current_value triggers KpiGoalObserver::updated() → recalc achievement_pct
        $goal->update(['current_value' => $currentValue]);

        return $goal->fresh();
    }
}
