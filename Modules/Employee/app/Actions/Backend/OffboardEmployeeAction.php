<?php

namespace Modules\Employee\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\OffboardEmployeeData;
use Modules\Employee\Enums\EmployeeHistoryChangeType;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeHistory;

class OffboardEmployeeAction
{
    use AsAction;

    public function handle(Employee $employee, OffboardEmployeeData $data): Employee
    {
        // Kiểm tra direct reports chưa được reassign
        if ($employee->hasDirectReports() && $data->reassign_manager_id === null) {
            throw new \RuntimeException(
                'Nhân viên này đang quản lý ' . $employee->subordinates()->working()->count() . ' nhân viên. Vui lòng chỉ định manager mới trước khi offboard.'
            );
        }

        $newStatus   = $data->separation_type === 'resigned' ? EmployeeStatus::Resigned : EmployeeStatus::Terminated;
        $oldStatus   = $employee->status->value;
        $oldSalary   = $employee->salary_base;

        DB::transaction(function () use ($employee, $data, $newStatus, $oldStatus, $oldSalary): void {
            // Reassign direct reports nếu có
            if ($data->reassign_manager_id !== null) {
                Employee::withoutTenant()
                    ->where('manager_id', $employee->id)
                    ->working()
                    ->update(['manager_id' => $data->reassign_manager_id]);
            }

            // Cập nhật employee
            Employee::$skipHistoryTracking = true;
            try {
                $employee->update([
                    'status'             => $newStatus->value,
                    'resigned_at'        => $data->effective_date,
                    'resignation_reason' => $data->reason,
                    'left_at'            => $data->effective_date,
                    'updated_by'         => auth()->id(),
                ]);
            } finally {
                Employee::$skipHistoryTracking = false;
            }

            // Ghi history separation
            EmployeeHistory::create([
                'organization_id'    => $employee->organization_id,
                'employee_id'        => $employee->id,
                'changed_by'         => auth()->id(),
                'change_type'        => EmployeeHistoryChangeType::Separation->value,
                'old_status'         => $oldStatus,
                'new_status'         => $newStatus->value,
                'old_salary_base'    => $oldSalary,
                'new_salary_base'    => $oldSalary,
                'old_job_title_id'   => $employee->job_title_id,
                'new_job_title_id'   => $employee->job_title_id,
                'old_department_id'  => $employee->department_id,
                'new_department_id'  => $employee->department_id,
                'old_branch_id'      => $employee->branch_id,
                'new_branch_id'      => $employee->branch_id,
                'old_manager_id'     => $employee->manager_id,
                'new_manager_id'     => null,
                'old_employment_type' => $employee->employment_type->value,
                'new_employment_type' => $employee->employment_type->value,
                'effective_date'     => $data->effective_date,
                'note'               => $data->reason,
            ]);

            // Vô hiệu hóa tài khoản user
            if ($employee->user_id) {
                \App\Models\User::where('id', $employee->user_id)->update(['status' => 'inactive']);
            }
        });

        return $employee->fresh();
    }
}
