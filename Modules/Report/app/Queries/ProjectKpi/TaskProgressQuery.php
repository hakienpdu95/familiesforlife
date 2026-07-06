<?php

namespace Modules\Report\Queries\ProjectKpi;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Task\Models\Task;

final class TaskProgressQuery
{
    public function __construct(
        private readonly int    $orgId,
        private readonly ?int   $projectId    = null,
        private readonly ?int   $departmentId = null,
        private readonly ?string $status      = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            projectId:    !empty($params['project_id'])    ? (int) $params['project_id']    : null,
            departmentId: !empty($params['department_id']) ? (int) $params['department_id'] : null,
            status:       $params['status']        ?? null,
        );
    }

    private function base()
    {
        return Task::where('tasks.organization_id', $this->orgId)
            ->where('tasks.is_leaf', true)
            ->where('tasks.is_archived', false)
            ->when($this->projectId,    fn ($q) => $q->where('tasks.project_id',    $this->projectId))
            ->when($this->departmentId, fn ($q) => $q->join('projects', 'projects.id', '=', 'tasks.project_id')
                ->where('projects.department_id', $this->departmentId))
            ->when($this->status, fn ($q) => $q->where('tasks.status', $this->status));
    }

    public function summary(): array
    {
        $counts = (clone $this->base())
            ->selectRaw("
                COUNT(*) as total_tasks,
                SUM(tasks.status = 'done') as done,
                SUM(tasks.status = 'in_progress') as in_progress,
                SUM(tasks.status = 'todo') as todo,
                SUM(tasks.due_date < CURDATE() AND tasks.status != 'done') as overdue,
                COALESCE(SUM(tasks.estimated_hours), 0) as total_estimated_hours,
                COALESCE(SUM(tasks.logged_hours), 0) as total_logged_hours
            ")
            ->first();

        $total    = (int) ($counts->total_tasks ?? 0);
        $done     = (int) ($counts->done ?? 0);
        $estHrs   = (float) ($counts->total_estimated_hours ?? 0);
        $logHrs   = (float) ($counts->total_logged_hours ?? 0);

        return [
            'total_tasks'            => $total,
            'done'                   => $done,
            'in_progress'            => (int) ($counts->in_progress ?? 0),
            'todo'                   => (int) ($counts->todo ?? 0),
            'overdue'                => (int) ($counts->overdue ?? 0),
            'completion_pct'         => $total > 0 ? round($done / $total * 100, 1) : 0,
            'total_estimated_hours'  => $estHrs,
            'total_logged_hours'     => $logHrs,
            'time_variance_pct'      => $estHrs > 0 ? round(($logHrs - $estHrs) / $estHrs * 100, 1) : 0,
        ];
    }

    public function byPriority(): Collection
    {
        return (clone $this->base())
            ->selectRaw("tasks.priority, COUNT(*) as count, SUM(tasks.status='done') as done, SUM(tasks.due_date < CURDATE() AND tasks.status != 'done') as overdue")
            ->groupBy('tasks.priority')
            ->orderByRaw("FIELD(tasks.priority,'urgent','high','medium','low')")
            ->get();
    }

    public function byAssignee(): Collection
    {
        return (clone $this->base())
            ->join('employees', 'employees.id', '=', 'tasks.employee_id')
            ->selectRaw("
                employees.id as employee_id,
                employees.full_name,
                COUNT(*) as tasks_total,
                SUM(tasks.status='done') as tasks_done,
                COALESCE(SUM(tasks.estimated_hours),0) as estimated_hours,
                COALESCE(SUM(tasks.logged_hours),0) as logged_hours,
                SUM(tasks.due_date < CURDATE() AND tasks.status!='done') as overdue
            ")
            ->groupBy('employees.id', 'employees.full_name')
            ->orderByDesc('tasks_total')
            ->limit(15)
            ->get();
    }

    public function overdueTasks(): Collection
    {
        return (clone $this->base())
            ->where('tasks.status', '!=', 'done')
            ->whereDate('tasks.due_date', '<', now())
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->leftJoin('employees', 'employees.id', '=', 'tasks.employee_id')
            ->selectRaw('tasks.id as task_id, tasks.title, projects.name as project, employees.full_name as assignee, tasks.due_date, DATEDIFF(NOW(), tasks.due_date) as days_overdue')
            ->orderByDesc('days_overdue')
            ->limit(20)
            ->get();
    }

    public function weeklyVelocity(): Collection
    {
        return Task::where('organization_id', $this->orgId)
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->when($this->projectId, fn ($q) => $q->where('project_id', $this->projectId))
            ->selectRaw("YEARWEEK(completed_at, 1) as yw, DATE_FORMAT(MIN(completed_at), '%Y-W%u') as week, SUM(story_points) as story_points_done")
            ->groupBy('yw')
            ->orderBy('yw')
            ->limit(12)
            ->get();
    }
}
