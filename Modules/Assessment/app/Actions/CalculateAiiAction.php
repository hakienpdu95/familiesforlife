<?php

namespace Modules\Assessment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KpiGoal\Models\AiImpactSnapshot;

/**
 * AI Impact Index (AII)
 *
 * Formula (tài liệu gốc):
 *   AII = (Productivity Gain × 40%) + (Quality Improvement × 30%) + (Time Saving × 30%)
 *
 * Trong đó:
 *   Productivity Gain    = avg improvement_pct WHERE impact_category = 'productivity'
 *   Quality Improvement  = avg improvement_pct WHERE impact_category = 'quality'
 *   Time Saving          = avg improvement_pct WHERE impact_type     = 'time_saving'
 */
class CalculateAiiAction
{
    use AsAction;

    public function handle(int $employeeId, string $periodStart, string $periodEnd): float
    {
        $snapshots = AiImpactSnapshot::withoutTenant()
            ->where('employee_id', $employeeId)
            ->where('period_start', '>=', $periodStart)
            ->where('period_end',   '<=', $periodEnd)
            ->get(['impact_category', 'impact_type', 'improvement_pct']);

        $productivity = $snapshots->where('impact_category', 'productivity')->avg('improvement_pct') ?? 0.0;
        $quality      = $snapshots->where('impact_category', 'quality')->avg('improvement_pct')      ?? 0.0;
        $timeSaving   = $snapshots->where('impact_type', 'time_saving')->avg('improvement_pct')      ?? 0.0;

        return round(
            $productivity * 0.40 +
            $quality      * 0.30 +
            $timeSaving   * 0.30,
        2);
    }
}
