<?php

namespace Modules\Report\Queries\ProjectKpi;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Project\Models\Project;
use Modules\Task\Models\Task;

final class ProjectOverviewQuery
{
    public function __construct(
        private readonly int     $orgId,
        private readonly string  $dateFrom,
        private readonly string  $dateTo,
        private readonly ?int    $branchId     = null,
        private readonly ?int    $departmentId = null,
        private readonly ?string $status       = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            dateFrom:     $params['date_from']     ?? now()->startOfYear()->toDateString(),
            dateTo:       $params['date_to']       ?? now()->toDateString(),
            branchId:     !empty($params['branch_id'])     ? (int) $params['branch_id']     : null,
            departmentId: !empty($params['department_id']) ? (int) $params['department_id'] : null,
            status:       $params['status']        ?? null,
        );
    }

    private function base()
    {
        return Project::where('projects.organization_id', $this->orgId)
            ->when($this->branchId,     fn ($q) => $q->where('branch_id',     $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->when($this->status,       fn ($q) => $q->where('status',        $this->status));
    }

    public function summary(): array
    {
        $counts = (clone $this->base())
            ->selectRaw("
                COUNT(*) as total_projects,
                SUM(status = 'active') as active,
                SUM(status = 'completed') as completed,
                SUM(status = 'on_hold') as on_hold,
                SUM(status = 'cancelled') as cancelled,
                COALESCE(SUM(budget), 0) as total_budget,
                SUM(end_date < CURDATE() AND status NOT IN ('completed','cancelled')) as overdue_count
            ")
            ->first();

        $total    = (int) ($counts->total_projects ?? 0);
        $completed = (int) ($counts->completed ?? 0);

        return [
            'total_projects' => $total,
            'active'         => (int) ($counts->active    ?? 0),
            'completed'      => $completed,
            'on_hold'        => (int) ($counts->on_hold   ?? 0),
            'cancelled'      => (int) ($counts->cancelled ?? 0),
            'overdue_count'  => (int) ($counts->overdue_count ?? 0),
            'total_budget'   => (float) ($counts->total_budget ?? 0),
            'currency'       => 'VND',
        ];
    }

    public function byStatus(): Collection
    {
        return (clone $this->base())
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(budget), 0) as budget')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();
    }

    public function byPriority(): Collection
    {
        return (clone $this->base())
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->orderByDesc('count')
            ->get();
    }

    public function projectsAtRisk(): Collection
    {
        $taskStats = Task::whereIn('project_id', (clone $this->base())->pluck('id'))
            ->where('is_leaf', true)
            ->where('is_archived', false)
            ->selectRaw('project_id, COUNT(*) as tasks_total, SUM(status="done") as tasks_done')
            ->groupBy('project_id')
            ->pluck(null, 'project_id');

        return (clone $this->base())
            ->where('status', 'active')
            ->whereDate('end_date', '<=', now()->addDays(30))
            ->orderBy('end_date')
            ->limit(10)
            ->get()
            ->map(function ($p) use ($taskStats) {
                $ts = $taskStats[$p->id] ?? null;
                $total = (int) ($ts?->tasks_total ?? 0);
                $done  = (int) ($ts?->tasks_done  ?? 0);
                return [
                    'project_id'       => $p->id,
                    'name'             => $p->name,
                    'status'           => $p->status,
                    'end_date'         => $p->end_date,
                    'days_remaining'   => max(0, now()->diffInDays($p->end_date, false)),
                    'tasks_total'      => $total,
                    'tasks_done'       => $done,
                    'completion_pct'   => $total > 0 ? round($done / $total * 100, 1) : 0,
                    'is_behind_schedule' => $total > 0 && ($done / $total) < 0.5 && now()->diffInDays($p->end_date, false) < 14,
                ];
            });
    }

    public function byDepartment(): Collection
    {
        return (clone $this->base())
            ->leftJoin('departments', 'departments.id', '=', 'projects.department_id')
            ->selectRaw("
                departments.name,
                COUNT(*) as count,
                SUM(projects.status = 'active') as active,
                SUM(projects.status = 'completed') as completed
            ")
            ->groupBy('departments.name')
            ->orderByDesc('count')
            ->get();
    }
}
