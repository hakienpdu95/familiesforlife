<?php

namespace Modules\Report\Queries\Hr;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\PerformanceReview\Models\PerformanceReview;
use Modules\PerformanceReview\Models\ReviewScore;

final class PerformanceReportQuery
{
    public function __construct(
        private readonly int    $orgId,
        private readonly ?string $period       = null,
        private readonly ?int    $departmentId = null,
        private readonly ?int    $branchId     = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            period:       $params['period']        ?? null,
            departmentId: $params['department_id'] ? (int) $params['department_id'] : null,
            branchId:     $params['branch_id']     ? (int) $params['branch_id']     : null,
        );
    }

    private function base()
    {
        return PerformanceReview::where('organization_id', $this->orgId)
            ->when($this->period, fn ($q) => $q->where('period', $this->period))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->when($this->branchId,     fn ($q) => $q->where('branch_id',     $this->branchId));
    }

    public function summary(): array
    {
        $counts = (clone $this->base())
            ->selectRaw("
                COUNT(*) as total_reviews,
                SUM(status = 'completed') as completed,
                SUM(status = 'pending') as pending,
                SUM(status = 'draft') as draft,
                AVG(CASE WHEN status = 'completed' THEN overall_score END) as avg_overall_score
            ")
            ->first();

        $total     = (int) ($counts->total_reviews ?? 0);
        $completed = (int) ($counts->completed ?? 0);

        return [
            'total_reviews'        => $total,
            'completed'            => $completed,
            'pending'              => (int) ($counts->pending ?? 0),
            'draft'                => (int) ($counts->draft ?? 0),
            'avg_overall_score'    => round((float) ($counts->avg_overall_score ?? 0), 2),
            'completion_rate_pct'  => $total > 0 ? round($completed / $total * 100, 1) : 0,
        ];
    }

    public function scoreDistribution(): Collection
    {
        return (clone $this->base())
            ->where('status', 'completed')
            ->selectRaw('overall_rating, COUNT(*) as count')
            ->groupBy('overall_rating')
            ->orderByRaw("FIELD(overall_rating,'excellent','above_expected','meets','below','poor')")
            ->get()
            ->pipe(function ($rows) {
                $total = $rows->sum('count');
                return $rows->map(fn ($r) => [
                    'rating' => $r->overall_rating,
                    'count'  => (int) $r->count,
                    'pct'    => $total > 0 ? round($r->count / $total * 100, 1) : 0,
                ]);
            });
    }

    public function byDepartment(): Collection
    {
        return (clone $this->base())
            ->join('departments', 'departments.id', '=', 'performance_reviews.department_id')
            ->selectRaw('
                departments.id as department_id,
                departments.name,
                COUNT(*) as total,
                SUM(performance_reviews.status = "completed") as completed,
                AVG(CASE WHEN performance_reviews.status = "completed" THEN overall_score END) as avg_score
            ')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'department_id' => $r->department_id,
                'name'          => $r->name,
                'total'         => (int) $r->total,
                'completed'     => (int) $r->completed,
                'avg_score'     => round((float) ($r->avg_score ?? 0), 2),
            ]);
    }

    public function criteriaBreakdown(): Collection
    {
        return ReviewScore::join('performance_reviews', 'performance_reviews.id', '=', 'review_scores.review_id')
            ->where('performance_reviews.organization_id', $this->orgId)
            ->where('performance_reviews.status', 'completed')
            ->when($this->period, fn ($q) => $q->where('performance_reviews.period', $this->period))
            ->selectRaw('criteria_key, criteria_name, AVG(score) as avg_score, AVG(weight) as avg_weight')
            ->groupBy('criteria_key', 'criteria_name')
            ->orderByRaw('AVG(weight) DESC')
            ->get();
    }

    public function topPerformers(): Collection
    {
        return (clone $this->base())
            ->where('status', 'completed')
            ->join('employees', 'employees.id', '=', 'performance_reviews.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('employees.id as employee_id, employees.full_name, departments.name as department, MAX(overall_score) as overall_score')
            ->groupBy('employees.id', 'employees.full_name', 'departments.name')
            ->orderByDesc('overall_score')
            ->limit(10)
            ->get();
    }

    public function lowPerformers(): Collection
    {
        return (clone $this->base())
            ->where('status', 'completed')
            ->join('employees', 'employees.id', '=', 'performance_reviews.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('employees.id as employee_id, employees.full_name, departments.name as department, MIN(overall_score) as overall_score')
            ->groupBy('employees.id', 'employees.full_name', 'departments.name')
            ->having('overall_score', '<', 2.5)
            ->orderBy('overall_score')
            ->limit(10)
            ->get();
    }

    public function periodComparison(): Collection
    {
        return PerformanceReview::where('organization_id', $this->orgId)
            ->where('status', 'completed')
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->selectRaw('period, AVG(overall_score) as avg_score, COUNT(*) as count')
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
}
