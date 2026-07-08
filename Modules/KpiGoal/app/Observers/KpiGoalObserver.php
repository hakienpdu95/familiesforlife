<?php

namespace Modules\KpiGoal\Observers;

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
    }
}
