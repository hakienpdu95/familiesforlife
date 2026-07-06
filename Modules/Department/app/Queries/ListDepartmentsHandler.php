<?php

namespace Modules\Department\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Department\Models\Department;

class ListDepartmentsHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'name', 'code', 'function', 'status', 'depth', 'path', 'created_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListDepartmentsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'path';

        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = Department::withoutTenant()
            ->select('departments.*')
            ->with(['branch:id,name,code', 'parent:id,name,code'])
            ->withCount('children')
            ->where('departments.organization_id', TenantContext::getOrganizationId());

        // ── Text search (OR) ────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('departments.name', 'like', $term)
                    ->orWhere('departments.code', 'like', $term)
                    ->orWhere('departments.budget_code', 'like', $term);
            });
        }

        // ── Exact filters ───────────────────────────────────────────────────
        if ($query->branchId !== null) {
            $q->where('departments.branch_id', $query->branchId);
        }

        if ($query->function !== null && $query->function !== '') {
            $q->where('departments.function', $query->function);
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('departments.status', $query->status);
        }

        if ($query->parentId !== null) {
            $q->where('departments.parent_id', $query->parentId);
        }

        // ── Date range ──────────────────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->where('departments.created_at', '>=', $query->dateFrom . ' 00:00:00');
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->where('departments.created_at', '<=', $query->dateTo . ' 23:59:59');
        }

        // ── Sort ────────────────────────────────────────────────────────────
        $q->orderBy('departments.' . $sortField, $sortDir);

        if ($sortField !== 'id') {
            $q->orderBy('departments.id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
