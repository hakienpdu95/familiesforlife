<?php

namespace Modules\Project\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Project\Models\Project;

class ListProjectsHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'name', 'code', 'status', 'priority', 'category',
        'start_date', 'end_date', 'budget', 'created_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListProjectsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $isSuperAdmin = auth()->user()?->hasRole('super-admin') ?? false;

        $q = Project::withoutTenant()
            ->select('projects.*')
            ->withCount('activeMembers')
            ->with([
                'branch:id,name,code',
                'department:id,name,code',
                'owner:id,full_name,employee_code',
            ]);

        if (! $isSuperAdmin) {
            $q->where('projects.organization_id', TenantContext::getOrganizationId());
        }

        // ── Text search (OR) ────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('projects.name',        'like', $term)
                    ->orWhere('projects.code',        'like', $term)
                    ->orWhere('projects.description', 'like', $term);
            });
        }

        // ── Exact filters ───────────────────────────────────────────────────
        if ($query->status !== null && $query->status !== '') {
            $q->where('projects.status', $query->status);
        }

        if ($query->priority !== null && $query->priority !== '') {
            $q->where('projects.priority', $query->priority);
        }

        if ($query->category !== null && $query->category !== '') {
            $q->where('projects.category', $query->category);
        }

        if ($query->branchId !== null) {
            $q->where('projects.branch_id', $query->branchId);
        }

        if ($query->departmentId !== null) {
            $q->where('projects.department_id', $query->departmentId);
        }

        if ($query->ownerId !== null) {
            $q->where('projects.owner_id', $query->ownerId);
        }

        // ── Date range on start_date ────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->where('projects.start_date', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->where('projects.start_date', '<=', $query->dateTo);
        }

        // ── Sort ────────────────────────────────────────────────────────────
        $q->orderBy('projects.' . $sortField, $sortDir);

        if ($sortField !== 'name') {
            $q->orderBy('projects.name', 'asc');
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
