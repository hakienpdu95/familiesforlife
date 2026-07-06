<?php

namespace Modules\Employee\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Employee\Models\Employee;

class ListEmployeesHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'full_name', 'employee_code', 'email', 'status', 'employment_type',
        'hired_at', 'created_at', 'snap_job_title', 'snap_branch_name', 'snap_dept_name',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListEmployeesQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'full_name';

        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = Employee::withoutTenant()
            ->select('employees.*')
            ->with(['branch:id,name,code', 'department:id,name,code', 'jobTitle:id,name,level', 'manager:id,full_name,employee_code',
                'media' => fn ($q) => $q->where('collection_name', 'avatar'),
            ])
            ->where('employees.organization_id', TenantContext::getOrganizationId());

        // ── Text search (OR) ────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('employees.full_name',     'like', $term)
                    ->orWhere('employees.employee_code', 'like', $term)
                    ->orWhere('employees.email',          'like', $term)
                    ->orWhere('employees.phone',          'like', $term)
                    ->orWhere('employees.national_id',    'like', $term);
            });
        }

        // ── Exact filters ───────────────────────────────────────────────────
        if ($query->status !== null && $query->status !== '') {
            $q->where('employees.status', $query->status);
        }

        if ($query->employmentType !== null && $query->employmentType !== '') {
            $q->where('employees.employment_type', $query->employmentType);
        }

        if ($query->branchId !== null) {
            $q->where('employees.branch_id', $query->branchId);
        }

        if ($query->departmentId !== null) {
            $q->where('employees.department_id', $query->departmentId);
        }

        if ($query->jobTitleId !== null) {
            $q->where('employees.job_title_id', $query->jobTitleId);
        }

        // ── Date range on hired_at ──────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->where('employees.hired_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->where('employees.hired_at', '<=', $query->dateTo);
        }

        // ── Sort ────────────────────────────────────────────────────────────
        $q->orderBy('employees.' . $sortField, $sortDir);

        if ($sortField !== 'employee_code') {
            $q->orderBy('employees.employee_code', 'asc');
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
