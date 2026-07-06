<?php

namespace Modules\Sop\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Sop\Models\SopProcess;

class ListSopProcessesHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'code', 'title', 'status', 'type', 'version', 'created_at', 'effective_date', 'expired_date',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListSopProcessesQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $orgId = TenantContext::getOrganizationId();

        $q = SopProcess::withoutTenant()
            ->select('sop_processes.*')
            ->where('sop_processes.organization_id', $orgId)
            ->with(['owner:id,name', 'department:id,name', 'branch:id,name']);

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('sop_processes.code',  'like', $term)
                    ->orWhere('sop_processes.title', 'like', $term);
            });
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('sop_processes.status', $query->status);
        }

        if ($query->type !== null && $query->type !== '') {
            $q->where('sop_processes.type', $query->type);
        }

        if ($query->departmentId !== null) {
            $q->where('sop_processes.department_id', $query->departmentId);
        }

        if ($query->branchId !== null) {
            $q->where('sop_processes.branch_id', $query->branchId);
        }

        if ($query->ownerId !== null) {
            $q->where('sop_processes.owner_id', $query->ownerId);
        }

        $q->orderBy('sop_processes.' . $sortField, $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
