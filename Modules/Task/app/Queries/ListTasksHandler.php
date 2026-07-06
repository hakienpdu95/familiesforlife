<?php

namespace Modules\Task\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Task\Models\Task;

class ListTasksHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'title', 'status', 'priority', 'task_type',
        'due_date', 'created_at', 'sort_order', 'depth',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListTasksQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Task::withoutTenant()
            ->select('tasks.*')
            ->with([
                'employee:id,full_name',
                'project:id,name,code',
            ])
            ->where('tasks.organization_id', TenantContext::getOrganizationId())
            ->where('tasks.is_archived', $query->isArchived)
            ->whereNull('tasks.deleted_at');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('tasks.title', 'like', $term);
            });
        }

        if ($query->projectId !== null) {
            $q->where('tasks.project_id', $query->projectId);
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('tasks.status', $query->status);
        }

        if ($query->priority !== null && $query->priority !== '') {
            $q->where('tasks.priority', $query->priority);
        }

        if ($query->taskType !== null && $query->taskType !== '') {
            $q->where('tasks.task_type', $query->taskType);
        }

        if ($query->employeeId !== null) {
            $q->where('tasks.employee_id', $query->employeeId);
        }

        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->where('tasks.due_date', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->where('tasks.due_date', '<=', $query->dateTo);
        }

        $q->orderBy('tasks.' . $sortField, $sortDir);

        if ($sortField !== 'title') {
            $q->orderBy('tasks.title', 'asc');
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
