<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\Department\Models\Department;
use Modules\Employee\Actions\Backend\DestroyEmployeeAction;
use Modules\Employee\Actions\Backend\StoreEmployeeAction;
use Modules\Employee\Actions\Backend\UpdateEmployeeAction;
use Modules\Employee\Data\Requests\StoreEmployeeData;
use Modules\Employee\Data\Requests\UpdateEmployeeData;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Enums\EmploymentType;
use Modules\Employee\Models\Employee;
use Modules\JobTitle\Models\JobTitle;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Employee::class, 'employee');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_probation,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_on_leave,
                 SUM(CASE WHEN status IN (?,?) THEN 1 ELSE 0 END) as total_inactive,
                 SUM(CASE WHEN employment_type = ? THEN 1 ELSE 0 END) as total_contractor',
                [
                    EmployeeStatus::Active->value,
                    EmployeeStatus::Probation->value,
                    EmployeeStatus::OnLeave->value,
                    EmployeeStatus::Resigned->value,
                    EmployeeStatus::Terminated->value,
                    EmploymentType::Contractor->value,
                ]
            )
            ->first();

        $totalAll        = (int) ($counts->total_all        ?? 0);
        $totalActive     = (int) ($counts->total_active     ?? 0);
        $totalProbation  = (int) ($counts->total_probation  ?? 0);
        $totalOnLeave    = (int) ($counts->total_on_leave   ?? 0);
        $totalInactive   = (int) ($counts->total_inactive   ?? 0);
        $totalContractor = (int) ($counts->total_contractor ?? 0);

        // Cảnh báo hợp đồng sắp hết hạn
        $contractAlerts = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->whereNotNull('contract_end')
            ->whereIn('status', [EmployeeStatus::Active->value, EmployeeStatus::Probation->value])
            ->where('contract_end', '<=', now()->addDays(90)->toDateString())
            ->where('contract_end', '>=', now()->toDateString())
            ->orderBy('contract_end')
            ->get(['id', 'full_name', 'employee_code', 'contract_end', 'snap_dept_name', 'snap_job_title']);

        $contractAlerts30 = $contractAlerts->filter(fn ($e) => $e->contract_end <= now()->addDays(30))->count();
        $contractAlerts90 = $contractAlerts->count();

        $statuses = collect(EmployeeStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $employmentTypes = collect(EmploymentType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $branches = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($b) => ['value' => $b->id, 'text' => $b->name . ' (' . $b->code . ')'])
            ->all();

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($d) => ['value' => $d->id, 'text' => $d->name . ' (' . $d->code . ')'])
            ->all();

        return view('employee::index', compact(
            'totalAll', 'totalActive', 'totalProbation', 'totalOnLeave', 'totalInactive', 'totalContractor',
            'contractAlerts', 'contractAlerts30', 'contractAlerts90',
            'statuses', 'employmentTypes', 'branches', 'departments'
        ));
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        $branches = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $jobTitles = JobTitle::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'name', 'category', 'level']);

        $managers = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', EmployeeStatus::Active->value)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code']);

        $statuses = collect(EmployeeStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $employmentTypes = collect(EmploymentType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('employee::create', compact(
            'branches', 'departments', 'jobTitles', 'managers', 'statuses', 'employmentTypes', 'organizations', 'defaultOrgId', 'orgLocked'
        ));
    }

    public function store(Request $request, StoreEmployeeAction $action): RedirectResponse
    {
        if (org_at_limit('limit.employees', Employee::count())) {
            return back()->withInput()->withErrors([
                'limit' => 'Bạn đã đạt giới hạn nhân viên (' . org_limit('limit.employees') . ') của gói hiện tại. Vui lòng nâng cấp để thêm.',
            ]);
        }

        $data     = StoreEmployeeData::validateAndCreate($request->all());
        $employee = $action->handle($data);

        return redirect()->route('backend.employees.show', $employee)
            ->with('success', 'Nhân viên "' . $employee->full_name . '" đã được tạo thành công.');
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'branch', 'department', 'jobTitle', 'manager',
            'employeeDepartments.department',
            'history.changedBy',
            'createdBy', 'updatedBy',
        ]);

        return view('employee::show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $orgId = TenantContext::getOrganizationId();

        $branches = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $jobTitles = JobTitle::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'name', 'category', 'level']);

        $managers = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', EmployeeStatus::Active->value)
            ->where('id', '!=', $employee->id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code']);

        $statuses = collect(EmployeeStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $employmentTypes = collect(EmploymentType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $employee->load(['branch', 'department', 'jobTitle']);

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('employee::edit', compact(
            'employee', 'branches', 'departments', 'jobTitles', 'managers', 'statuses', 'employmentTypes', 'organizations', 'orgLocked'
        ));
    }

    public function update(Request $request, Employee $employee, UpdateEmployeeAction $action): RedirectResponse
    {
        $data = UpdateEmployeeData::validateAndCreate($request->all());
        $action->handle($employee, $data);

        return redirect()->route('backend.employees.show', $employee)
            ->with('success', 'Cập nhật nhân viên thành công.');
    }

    public function destroy(Request $request, Employee $employee, DestroyEmployeeAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($employee);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa nhân viên "' . $name . '".' ]);
        }

        return redirect()->route('backend.employees.index')
            ->with('success', 'Đã xóa nhân viên "' . $name . '".');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }
}
