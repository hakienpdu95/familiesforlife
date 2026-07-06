<?php

namespace Modules\KpiGoal\Observers;

use Modules\Assessment\Events\LowKpiAlert;
use Modules\KpiGoal\Models\KpiGoal;

class KpiGoalObserver
{
    public function updated(KpiGoal $goal): void
    {
        if (!$goal->wasChanged('current_value')) {
            return;
        }

        $pct = $goal->direction->calcAchievement(
            (float) $goal->current_value,
            (float) $goal->target_value,
        );

        // Update without triggering observer loop
        KpiGoal::withoutEvents(function () use ($goal, $pct) {
            $goal->updateQuietly(['achievement_pct' => $pct]);
        });

        if ($pct < 50) {
            event(new LowKpiAlert(
                goalId: $goal->id,
                achievementPct: $pct,
                employeeId: $goal->employee_id ?? null,
                organizationId: $goal->organization_id ?? null,
            ));
        }
    }
}
