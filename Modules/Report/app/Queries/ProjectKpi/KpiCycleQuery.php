<?php

namespace Modules\Report\Queries\ProjectKpi;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\KpiGoal\Models\KpiGoal;
use Modules\KpiGoal\Models\KpiSnapshot;

final class KpiCycleQuery
{
    public function __construct(
        private readonly int    $orgId,
        private readonly ?string $cycleLabel   = null,
        private readonly ?int    $departmentId = null,
        private readonly ?int    $employeeId   = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            cycleLabel:   $params['cycle_label']   ?? null,
            departmentId: !empty($params['department_id']) ? (int) $params['department_id'] : null,
            employeeId:   !empty($params['employee_id'])   ? (int) $params['employee_id']   : null,
        );
    }

    private function base()
    {
        return KpiGoal::where('kpi_goals.organization_id', $this->orgId)
            ->when($this->cycleLabel,   fn ($q) => $q->where('kpi_goals.cycle_label',          $this->cycleLabel))
            ->when($this->employeeId,   fn ($q) => $q->where('kpi_goals.employee_id',           $this->employeeId))
            ->when($this->departmentId, fn ($q) => $q->join('employees as emp_dept', 'emp_dept.id', '=', 'kpi_goals.employee_id')
                ->where('emp_dept.department_id', $this->departmentId));
    }

    public function availableCycles(): Collection
    {
        return KpiGoal::where('organization_id', $this->orgId)
            ->selectRaw('DISTINCT cycle_label')
            ->orderByDesc('cycle_label')
            ->limit(8)
            ->pluck('cycle_label');
    }

    public function summary(): array
    {
        $counts = (clone $this->base())
            ->selectRaw("
                COUNT(*) as total_goals,
                SUM(achievement_pct >= 100) as achieved,
                SUM(achievement_pct >= 60 AND achievement_pct < 100) as partial,
                SUM(achievement_pct < 60) as missed,
                AVG(achievement_pct) as avg_achievement_pct,
                COUNT(DISTINCT kpi_goals.employee_id) as employees_with_goals
            ")
            ->first();

        $avgWeighted = (clone $this->base())
            ->selectRaw('AVG(achievement_pct * weight_percent / 100) as avg_ws')
            ->value('avg_ws');

        return [
            'cycle'                  => $this->cycleLabel ?? 'Tất cả',
            'total_goals'            => (int) ($counts->total_goals ?? 0),
            'achieved'               => (int) ($counts->achieved ?? 0),
            'partial'                => (int) ($counts->partial ?? 0),
            'missed'                 => (int) ($counts->missed ?? 0),
            'avg_achievement_pct'    => round((float) ($counts->avg_achievement_pct ?? 0), 1),
            'avg_weighted_score'     => round((float) ($avgWeighted ?? 0), 1),
            'employees_with_goals'   => (int) ($counts->employees_with_goals ?? 0),
        ];
    }

    public function achievementDistribution(): array
    {
        $base = (clone $this->base());
        $total = (clone $base)->count() ?: 1;
        $bands = [
            ['label' => '≥100%',  'min' => 100, 'max' => 9999],
            ['label' => '80-99%', 'min' => 80,  'max' => 99.9],
            ['label' => '60-79%', 'min' => 60,  'max' => 79.9],
            ['label' => '<60%',   'min' => 0,   'max' => 59.9],
        ];
        return collect($bands)->map(function ($b) use ($base, $total) {
            $count = (clone $base)->whereBetween('achievement_pct', [$b['min'], $b['max']])->count();
            return ['band' => $b['label'], 'count' => $count, 'pct' => round($count / $total * 100, 1)];
        })->all();
    }

    public function byGoalType(): Collection
    {
        return (clone $this->base())
            ->selectRaw('goal_type, COUNT(*) as count, AVG(achievement_pct) as avg_achievement, AVG(weight_percent) as avg_weight')
            ->groupBy('goal_type')
            ->orderByDesc('avg_weight')
            ->get();
    }

    public function byDepartment(): Collection
    {
        return KpiGoal::where('kpi_goals.organization_id', $this->orgId)
            ->when($this->cycleLabel, fn ($q) => $q->where('kpi_goals.cycle_label', $this->cycleLabel))
            ->join('employees', 'employees.id', '=', 'kpi_goals.employee_id')
            ->join('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('departments.id as department_id, departments.name, COUNT(DISTINCT kpi_goals.employee_id) as employee_count, AVG(achievement_pct) as avg_achievement, AVG(achievement_pct * weight_percent / 100) as avg_weighted_score')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('avg_weighted_score')
            ->get();
    }

    public function topPerformers(): Collection
    {
        return KpiGoal::where('kpi_goals.organization_id', $this->orgId)
            ->when($this->cycleLabel, fn ($q) => $q->where('kpi_goals.cycle_label', $this->cycleLabel))
            ->join('employees', 'employees.id', '=', 'kpi_goals.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('kpi_goals.employee_id, employees.full_name, departments.name as department, SUM(achievement_pct * weight_percent / 100) as weighted_score, COUNT(*) as goals_count')
            ->groupBy('kpi_goals.employee_id', 'employees.full_name', 'departments.name')
            ->orderByDesc('weighted_score')
            ->limit(10)
            ->get();
    }

    public function atRisk(): Collection
    {
        return (clone $this->base())
            ->where('kpi_goals.status', 'active')
            ->where('achievement_pct', '<', 60)
            ->join('employees', 'employees.id', '=', 'kpi_goals.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('kpi_goals.employee_id, employees.full_name, departments.name as department, AVG(achievement_pct) as current_achievement_pct, DATEDIFF(MAX(kpi_goals.cycle_end), NOW()) as days_remaining')
            ->groupBy('kpi_goals.employee_id', 'employees.full_name', 'departments.name')
            ->orderBy('current_achievement_pct')
            ->limit(10)
            ->get();
    }
}
