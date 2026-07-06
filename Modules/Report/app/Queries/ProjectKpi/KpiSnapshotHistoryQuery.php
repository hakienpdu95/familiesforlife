<?php

namespace Modules\Report\Queries\ProjectKpi;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\KpiGoal\Models\KpiSnapshot;
use Modules\Employee\Models\Employee;

final class KpiSnapshotHistoryQuery
{
    public function __construct(
        private readonly int  $orgId,
        private readonly ?int $employeeId   = null,
        private readonly ?int $departmentId = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            employeeId:   !empty($params['employee_id'])   ? (int) $params['employee_id']   : null,
            departmentId: !empty($params['department_id']) ? (int) $params['department_id'] : null,
        );
    }

    public function cycles(): Collection
    {
        return KpiSnapshot::join('employees', 'employees.id', '=', 'kpi_snapshots.employee_id')
            ->where('employees.organization_id', $this->orgId)
            ->when($this->departmentId, fn ($q) => $q->where('employees.department_id', $this->departmentId))
            ->selectRaw("
                cycle_label,
                COUNT(DISTINCT kpi_snapshots.employee_id) as employee_count,
                AVG(kpi_total_score) as avg_kpi_score,
                SUM(kpi_total_score >= 90) as band_a,
                SUM(kpi_total_score >= 75 AND kpi_total_score < 90) as band_b,
                SUM(kpi_total_score >= 60 AND kpi_total_score < 75) as band_c,
                SUM(kpi_total_score < 60) as band_d
            ")
            ->groupBy('cycle_label')
            ->orderBy('cycle_label')
            ->get()
            ->map(fn ($r) => [
                'cycle_label'      => $r->cycle_label,
                'employee_count'   => (int) $r->employee_count,
                'avg_kpi_score'    => round((float) $r->avg_kpi_score, 1),
                'score_distribution' => [
                    'A (≥90)'  => (int) $r->band_a,
                    'B (75-89)' => (int) $r->band_b,
                    'C (60-74)' => (int) $r->band_c,
                    'D (<60)'   => (int) $r->band_d,
                ],
            ]);
    }

    public function employeeTrend(): Collection
    {
        if (!$this->employeeId) return collect();

        return KpiSnapshot::where('employee_id', $this->employeeId)
            ->join('employees', 'employees.id', '=', 'kpi_snapshots.employee_id')
            ->where('employees.organization_id', $this->orgId)
            ->selectRaw('cycle_label, kpi_total_score')
            ->orderBy('cycle_label')
            ->get()
            ->map(fn ($r) => ['cycle' => $r->cycle_label, 'kpi_score' => round((float) $r->kpi_total_score, 1)]);
    }
}
