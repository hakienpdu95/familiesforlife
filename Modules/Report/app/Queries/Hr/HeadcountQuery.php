<?php

namespace Modules\Report\Queries\Hr;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeHistory;

final class HeadcountQuery
{
    public function __construct(
        private readonly int     $orgId,
        private readonly string  $dateFrom,
        private readonly string  $dateTo,
        private readonly ?int    $branchId     = null,
        private readonly ?int    $departmentId = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            dateFrom:     $params['date_from']     ?? now()->startOfMonth()->toDateString(),
            dateTo:       $params['date_to']       ?? now()->toDateString(),
            branchId:     $params['branch_id']     ? (int) $params['branch_id']     : null,
            departmentId: $params['department_id'] ? (int) $params['department_id'] : null,
        );
    }

    public function summary(): array
    {
        $base = Employee::withoutTenant()
            ->where('organization_id', $this->orgId)
            ->when($this->branchId,     fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId));

        $counts = (clone $base)
            ->selectRaw("
                SUM(status = 'active')     as total_active,
                SUM(status = 'probation')  as total_probation,
                SUM(status = 'on_leave')   as total_on_leave,
                SUM(status = 'resigned' AND DATE(left_at) BETWEEN ? AND ?)  as total_resigned,
                SUM(status IN ('active','probation','on_leave')) as total_working
            ", [$this->dateFrom, $this->dateTo])
            ->first();

        $newHires = (clone $base)
            ->whereIn('status', ['active','probation','on_leave','resigned'])
            ->whereBetween('hired_at', [$this->dateFrom, $this->dateTo])
            ->count();

        $resigned = (clone $base)
            ->where('status', 'resigned')
            ->whereBetween('left_at', [$this->dateFrom, $this->dateTo])
            ->count();

        return [
            'total_active'     => (int) ($counts->total_active ?? 0),
            'total_probation'  => (int) ($counts->total_probation ?? 0),
            'total_on_leave'   => (int) ($counts->total_on_leave ?? 0),
            'total_working'    => (int) ($counts->total_working ?? 0),
            'total_resigned'   => (int) ($counts->total_resigned ?? 0),
            'new_hires'        => $newHires,
            'net_change'       => $newHires - $resigned,
        ];
    }

    public function byStatus(): Collection
    {
        return Employee::withoutTenant()
            ->where('organization_id', $this->orgId)
            ->when($this->branchId,     fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->whereIn('status', ['active','probation','on_leave'])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByRaw("FIELD(status,'active','probation','on_leave')")
            ->get();
    }

    public function byDepartment(): Collection
    {
        return Employee::withoutTenant()
            ->where('employees.organization_id', $this->orgId)
            ->when($this->branchId, fn ($q) => $q->where('employees.branch_id', $this->branchId))
            ->whereIn('employees.status', ['active','probation','on_leave'])
            ->join('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('departments.id as department_id, departments.name, COUNT(employees.id) as count, departments.headcount_limit')
            ->groupBy('departments.id', 'departments.name', 'departments.headcount_limit')
            ->orderByDesc('count')
            ->limit(15)
            ->get();
    }

    public function byBranch(): Collection
    {
        return Employee::withoutTenant()
            ->where('employees.organization_id', $this->orgId)
            ->when($this->departmentId, fn ($q) => $q->where('employees.department_id', $this->departmentId))
            ->whereIn('employees.status', ['active','probation','on_leave'])
            ->join('branches', 'branches.id', '=', 'employees.branch_id')
            ->selectRaw('branches.id as branch_id, branches.name, COUNT(employees.id) as count')
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('count')
            ->get();
    }

    public function byEmploymentType(): Collection
    {
        return Employee::withoutTenant()
            ->where('organization_id', $this->orgId)
            ->when($this->branchId,     fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->whereIn('status', ['active','probation','on_leave'])
            ->selectRaw('employment_type, COUNT(*) as count')
            ->groupBy('employment_type')
            ->orderByDesc('count')
            ->get();
    }

    public function trend(): Collection
    {
        $hired = Employee::withoutTenant()
            ->where('organization_id', $this->orgId)
            ->when($this->branchId,     fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->whereBetween('hired_at', [$this->dateFrom, $this->dateTo])
            ->selectRaw("DATE_FORMAT(hired_at, '%Y-%m') as period, COUNT(*) as hired")
            ->groupBy('period')
            ->pluck('hired', 'period');

        $resigned = Employee::withoutTenant()
            ->where('organization_id', $this->orgId)
            ->when($this->branchId,     fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->whereNotNull('left_at')
            ->whereBetween('left_at', [$this->dateFrom, $this->dateTo])
            ->selectRaw("DATE_FORMAT(left_at, '%Y-%m') as period, COUNT(*) as resigned")
            ->groupBy('period')
            ->pluck('resigned', 'period');

        $periods = collect($hired->keys()->merge($resigned->keys())->unique()->sort()->values());

        return $periods->map(fn ($p) => [
            'period'   => $p,
            'hired'    => (int) ($hired[$p] ?? 0),
            'resigned' => (int) ($resigned[$p] ?? 0),
            'net'      => (int) ($hired[$p] ?? 0) - (int) ($resigned[$p] ?? 0),
        ]);
    }

    public function newHiresList(): Collection
    {
        return Employee::withoutTenant()
            ->where('employees.organization_id', $this->orgId)
            ->when($this->branchId,     fn ($q) => $q->where('employees.branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('employees.department_id', $this->departmentId))
            ->whereBetween('employees.hired_at', [$this->dateFrom, $this->dateTo])
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->select('employees.id', 'employees.full_name', 'employees.hired_at', 'departments.name as department_name')
            ->orderByDesc('employees.hired_at')
            ->limit(10)
            ->get();
    }
}
