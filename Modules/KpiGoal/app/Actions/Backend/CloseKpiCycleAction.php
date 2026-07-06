<?php

namespace Modules\KpiGoal\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KpiGoal\Enums\KpiGoalStatus;
use Modules\KpiGoal\Models\KpiGoal;
use Modules\KpiGoal\Models\KpiSnapshot;

class CloseKpiCycleAction
{
    use AsAction;

    public function handle(int $employeeId, string $cycleLabel): array
    {
        $goals = KpiGoal::where('employee_id', $employeeId)
            ->where('cycle_label', $cycleLabel)
            ->where('status', KpiGoalStatus::Active->value)
            ->get();

        if ($goals->isEmpty()) {
            throw new \RuntimeException('Không có mục tiêu nào đang theo dõi trong kỳ này.');
        }

        $totalWeight = $goals->sum('weight_percent');
        if ($totalWeight !== 100) {
            throw new \RuntimeException(
                "Tổng trọng số phải bằng 100% trước khi chốt kỳ. Hiện tại: {$totalWeight}%"
            );
        }

        $snapshots = DB::transaction(function () use ($goals, $employeeId, $cycleLabel) {
            $snapshots  = [];
            $totalScore = 0;

            foreach ($goals as $goal) {
                $weightedScore = round((float) $goal->achievement_pct * $goal->weight_percent / 100, 2);
                $totalScore   += $weightedScore;

                // Use DB::table to bypass immutability guard on KpiSnapshot::save()
                DB::table('kpi_snapshots')->insert([
                    'goal_id'        => $goal->id,
                    'employee_id'    => $employeeId,
                    'cycle_label'    => $cycleLabel,
                    'target_value'   => $goal->target_value,
                    'final_value'    => $goal->current_value,
                    'achievement_pct'=> $goal->achievement_pct,
                    'weight_percent' => $goal->weight_percent,
                    'weighted_score' => $weightedScore,
                    'snapped_by'     => auth()->id(),
                    'snapped_at'     => now(),
                ]);

                $goal->updateQuietly(['status' => KpiGoalStatus::Completed->value]);

                $snapshots[] = [
                    'goal_id'         => $goal->id,
                    'title'           => $goal->title,
                    'achievement_pct' => $goal->achievement_pct,
                    'weighted_score'  => $weightedScore,
                ];
            }

            // Round to 2 decimal places
            $totalScore = round($totalScore, 2);

            // Back-fill kpi_total_score on all snapshots for this cycle/employee
            DB::table('kpi_snapshots')
                ->where('employee_id', $employeeId)
                ->where('cycle_label', $cycleLabel)
                ->update(['kpi_total_score' => $totalScore]);

            activity()
                ->withProperties([
                    'employee_id'  => $employeeId,
                    'cycle_label'  => $cycleLabel,
                    'total_score'  => $totalScore,
                    'goal_count'   => count($snapshots),
                ])
                ->log('cycle_closed');

            return [
                'snapshots'   => $snapshots,
                'total_score' => $totalScore,
                'kpi_score_5' => round($totalScore / 100 * 5, 2),
            ];
        });

        return $snapshots;
    }
}
