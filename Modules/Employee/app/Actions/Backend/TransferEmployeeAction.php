<?php

namespace Modules\Employee\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\TransferEmployeeData;
use Modules\Employee\Enums\EmployeeHistoryChangeType;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeHistory;

class TransferEmployeeAction
{
    use AsAction;

    public function handle(Employee $employee, TransferEmployeeData $data): Employee
    {
        $updates   = [];
        $changeType = null;

        // Snapshot trước khi thay đổi
        $oldJobTitleId  = $employee->job_title_id;
        $oldDeptId      = $employee->department_id;
        $oldBranchId    = $employee->branch_id;
        $oldManagerId   = $employee->manager_id;
        $oldSalaryBase  = $employee->salary_base;
        $oldSnapLevel   = $employee->snap_job_level;

        if ($data->job_title_id !== null && $data->job_title_id !== $employee->job_title_id) {
            $updates['job_title_id'] = $data->job_title_id;
        }
        if ($data->department_id !== null && $data->department_id !== $employee->department_id) {
            $updates['department_id'] = $data->department_id;
        }
        if ($data->branch_id !== null && $data->branch_id !== $employee->branch_id) {
            $updates['branch_id'] = $data->branch_id;
        }
        if (array_key_exists('manager_id', $data->toArray()) && $data->manager_id !== $employee->manager_id) {
            $updates['manager_id'] = $data->manager_id;
        }
        if ($data->salary_base !== null) {
            $updates['salary_base'] = $data->salary_base;
        }
        if ($data->salary_currency !== null) {
            $updates['salary_currency'] = $data->salary_currency;
        }

        if (empty($updates)) {
            return $employee;
        }

        $updates['updated_by'] = auth()->id();
        $changeType            = $this->resolveChangeType($employee, $updates, $oldSnapLevel);

        DB::transaction(function () use ($employee, $updates, $changeType, $data, $oldJobTitleId, $oldDeptId, $oldBranchId, $oldManagerId, $oldSalaryBase): void {
            Employee::$skipHistoryTracking = true;

            try {
                $employee->update($updates);
            } finally {
                Employee::$skipHistoryTracking = false;
            }

            EmployeeHistory::create([
                'organization_id'    => $employee->organization_id,
                'employee_id'        => $employee->id,
                'changed_by'         => auth()->id(),
                'change_type'        => $changeType,
                'old_job_title_id'   => $oldJobTitleId,
                'new_job_title_id'   => $employee->job_title_id,
                'old_department_id'  => $oldDeptId,
                'new_department_id'  => $employee->department_id,
                'old_branch_id'      => $oldBranchId,
                'new_branch_id'      => $employee->branch_id,
                'old_manager_id'     => $oldManagerId,
                'new_manager_id'     => $employee->manager_id,
                'old_salary_base'    => $oldSalaryBase,
                'new_salary_base'    => $employee->salary_base,
                'old_status'         => $employee->status->value,
                'new_status'         => $employee->status->value,
                'old_employment_type' => $employee->employment_type->value,
                'new_employment_type' => $employee->employment_type->value,
                'effective_date'     => $data->effective_date,
                'note'               => $data->note,
            ]);
        });

        return $employee->fresh();
    }

    private function resolveChangeType(Employee $employee, array $updates, ?int $oldSnapLevel): string
    {
        $hasSalaryOnly = isset($updates['salary_base'])
            && !isset($updates['job_title_id'])
            && !isset($updates['department_id'])
            && !isset($updates['branch_id']);

        if ($hasSalaryOnly) {
            return EmployeeHistoryChangeType::SalaryChange->value;
        }

        if (isset($updates['job_title_id'])) {
            $newJt = \Modules\JobTitle\Models\JobTitle::withoutTenant()->find($updates['job_title_id']);
            $newLevel = $newJt?->level ?? 0;
            if ($newLevel > ($oldSnapLevel ?? 0)) return EmployeeHistoryChangeType::Promotion->value;
            if ($newLevel < ($oldSnapLevel ?? 0)) return EmployeeHistoryChangeType::Demotion->value;
            return EmployeeHistoryChangeType::Promotion->value;
        }

        if (isset($updates['department_id'])) {
            return EmployeeHistoryChangeType::DeptTransfer->value;
        }

        if (isset($updates['branch_id'])) {
            return EmployeeHistoryChangeType::BranchTransfer->value;
        }

        if (array_key_exists('manager_id', $updates)) {
            return EmployeeHistoryChangeType::ManagerChange->value;
        }

        return EmployeeHistoryChangeType::DeptTransfer->value;
    }
}
