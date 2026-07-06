<?php

namespace Modules\KpiGoal\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Events\ImpactSnapshotRecorded;
use Modules\Employee\Models\Employee;

class AiImpactSnapshot extends TenantAwareModel
{
    protected $table = 'ai_impact_snapshots';

    protected $fillable = [
        'organization_id',
        'employee_id',
        'kpi_goal_id',
        'impact_category',
        'impact_type',
        'baseline_value',
        'achieved_value',
        'improvement_pct',
        'investment_cost',
        'benefit_value',
        'roi_pct',
        'period_start',
        'period_end',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start'     => 'date',
            'period_end'       => 'date',
            'baseline_value'   => 'float',
            'achieved_value'   => 'float',
            'improvement_pct'  => 'float',
            'investment_cost'  => 'float',
            'benefit_value'    => 'float',
            'roi_pct'          => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $snapshot): void {
            // improvement_pct = (achieved - baseline) / baseline × 100
            if ($snapshot->baseline_value && $snapshot->baseline_value != 0 && $snapshot->achieved_value !== null) {
                $snapshot->improvement_pct = round(
                    ($snapshot->achieved_value - $snapshot->baseline_value) / $snapshot->baseline_value * 100,
                    2
                );
            }
            // roi_pct = (benefit - cost) / cost × 100
            if ($snapshot->investment_cost && $snapshot->investment_cost > 0) {
                $snapshot->roi_pct = round(
                    ($snapshot->benefit_value - $snapshot->investment_cost) / $snapshot->investment_cost * 100,
                    2
                );
            }
        });

        static::created(function (self $snapshot): void {
            event(new ImpactSnapshotRecorded($snapshot));
        });
    }

    // AII = Productivity×40% + Quality×30% + Time Saving×30%
    public static function calculateAii(int $employeeId, string $periodStart, string $periodEnd): float
    {
        $snapshots = static::withoutTenant()
            ->where('employee_id', $employeeId)
            ->whereBetween('period_start', [$periodStart, $periodEnd])
            ->get();

        $productivity = $snapshots->where('impact_category', 'productivity')->avg('improvement_pct') ?? 0;
        $quality      = $snapshots->where('impact_category', 'quality')->avg('improvement_pct') ?? 0;
        $timeSaving   = $snapshots->where('impact_type', 'time_saving')->avg('improvement_pct') ?? 0;

        return round($productivity * 0.40 + $quality * 0.30 + $timeSaving * 0.30, 2);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function kpiGoal(): BelongsTo
    {
        return $this->belongsTo(KpiGoal::class, 'kpi_goal_id');
    }
}
