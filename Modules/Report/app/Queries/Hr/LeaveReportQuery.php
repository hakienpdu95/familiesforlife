<?php

namespace Modules\Report\Queries\Hr;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveBalance;
use Modules\Employee\Models\Employee;

final class LeaveReportQuery
{
    public function __construct(
        private readonly int    $orgId,
        private readonly int    $year,
        private readonly ?string $leaveType    = null,
        private readonly ?int    $branchId     = null,
        private readonly ?int    $departmentId = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            year:         $params['year']          ? (int) $params['year']          : (int) now()->year,
            leaveType:    $params['leave_type']    ?? null,
            branchId:     $params['branch_id']     ? (int) $params['branch_id']     : null,
            departmentId: $params['department_id'] ? (int) $params['department_id'] : null,
        );
    }

    private function baseQuery()
    {
        return LeaveRequest::where('leave_requests.organization_id', $this->orgId)
            ->join('employees', 'employees.id', '=', 'leave_requests.employee_id')
            ->whereYear('leave_requests.date_from', $this->year)
            ->when($this->leaveType,    fn ($q) => $q->where('leave_requests.leave_type', $this->leaveType))
            ->when($this->branchId,     fn ($q) => $q->where('employees.branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('employees.department_id', $this->departmentId))
            ->where('leave_requests.status', '!=', 'cancelled');
    }

    public function summary(): array
    {
        $base = $this->baseQuery();

        $counts = (clone $base)
            ->selectRaw("
                COUNT(*) as total_requests,
                SUM(days_count) as total_days,
                SUM(leave_requests.status = 'pending') as pending_requests,
                COUNT(DISTINCT leave_requests.employee_id) as employees_with_leave
            ")
            ->first();

        $empCount = Employee::withoutTenant()
            ->where('organization_id', $this->orgId)
            ->whereIn('status', ['active','probation','on_leave'])
            ->when($this->branchId,     fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->count();

        $totalDays = (float) ($counts->total_days ?? 0);
        $avg       = $empCount > 0 ? round($totalDays / $empCount, 1) : 0;

        return [
            'total_requests'         => (int) ($counts->total_requests ?? 0),
            'total_days_taken'       => round($totalDays, 1),
            'pending_requests'       => (int) ($counts->pending_requests ?? 0),
            'employees_with_leave'   => (int) ($counts->employees_with_leave ?? 0),
            'avg_days_per_employee'  => $avg,
        ];
    }

    public function byType(): Collection
    {
        return $this->baseQuery()
            ->selectRaw('leave_requests.leave_type, COUNT(*) as requests, SUM(days_count) as days')
            ->groupBy('leave_requests.leave_type')
            ->orderByDesc('days')
            ->get();
    }

    public function byStatus(): Collection
    {
        return $this->baseQuery()
            ->selectRaw('leave_requests.status, COUNT(*) as count')
            ->groupBy('leave_requests.status')
            ->get();
    }

    public function byDepartment(): Collection
    {
        return $this->baseQuery()
            ->join('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw('departments.id as department_id, departments.name, COUNT(*) as requests, SUM(days_count) as days')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('days')
            ->limit(10)
            ->get();
    }

    public function monthlyTrend(): Collection
    {
        return $this->baseQuery()
            ->where('leave_requests.status', 'approved')
            ->selectRaw("DATE_FORMAT(leave_requests.date_from, '%Y-%m') as month, SUM(days_count) as days_taken")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function topRequesters(): Collection
    {
        return $this->baseQuery()
            ->where('leave_requests.status', 'approved')
            ->selectRaw('employees.id as employee_id, employees.full_name, SUM(days_count) as total_days')
            ->groupBy('employees.id', 'employees.full_name')
            ->orderByDesc('total_days')
            ->limit(10)
            ->get();
    }
}
