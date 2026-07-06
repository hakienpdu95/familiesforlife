<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Modules\Employee\Models\Employee;
use Modules\Leave\Models\LeaveBalance;

class LeaveBalanceController extends Controller
{
    /** Balance of current authenticated employee */
    public function me()
    {
        $user  = auth()->user();
        $orgId = TenantContext::getOrganizationId();
        $year  = now()->year;

        $employee = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        $balances = $employee
            ? LeaveBalance::where('employee_id', $employee->id)
                ->where('year', $year)
                ->with('policy')
                ->get()
            : collect();

        return view('leave::balances.me', compact('balances', 'year', 'employee'));
    }

    /** Balance of a specific employee (HR / Manager only) */
    public function forEmployee(Employee $employee)
    {
        $this->authorize('view', $employee);

        $year = request()->integer('year', now()->year);

        $balances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $year)
            ->with('policy')
            ->get();

        return view('leave::balances.employee', compact('balances', 'year', 'employee'));
    }
}
