<?php

namespace Modules\Employee\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\StoreEmployeeData;
use Modules\Employee\Events\EmployeeCreated;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeDepartment;

class StoreEmployeeAction
{
    use AsAction;

    public function handle(StoreEmployeeData $data): Employee
    {
        $employee = Employee::create([
            'uuid'                 => Str::uuid(),
            'organization_id'      => $data->organization_id,
            'user_id'              => $data->user_id,
            'branch_id'            => $data->branch_id,
            'department_id'        => $data->department_id,
            'job_title_id'         => $data->job_title_id,
            'manager_id'           => $data->manager_id,
            'employee_code'        => strtoupper(trim($data->employee_code)),
            'full_name'            => $data->full_name,
            'email'                => $data->email,
            'personal_email'       => $data->personal_email ?? null,
            'phone'                => $data->phone,
            'gender'               => $data->gender,
            'date_of_birth'        => $data->date_of_birth,
            'national_id'          => $data->national_id,
            'national_id_issued'   => $data->national_id_issued ?? null,
            'tax_code'             => $data->tax_code,
            'locale'               => $data->locale,
            'avatar_url'           => $data->avatar_url,
            'status'               => $data->status->value,
            'employment_type'      => $data->employment_type->value,
            'hired_at'             => $data->hired_at,
            'probation_end_date'   => $data->probation_end_date ?? null,
            'contract_start'       => $data->contract_start ?? null,
            'contract_end'         => $data->contract_end ?? null,
            'work_location'        => $data->work_location ?? null,
            'created_by'           => auth()->id(),
            'updated_by'           => auth()->id(),
        ]);

        // Tạo bản ghi employee_departments (primary)
        EmployeeDepartment::create([
            'employee_id'   => $employee->id,
            'department_id' => $data->department_id,
            'is_primary'    => true,
            'joined_at'     => $data->hired_at ?? now()->toDateString(),
        ]);

        // Ghi employee_history (hire)
        \Modules\Employee\Models\EmployeeHistory::create([
            'organization_id'    => $employee->organization_id,
            'employee_id'        => $employee->id,
            'changed_by'         => auth()->id(),
            'change_type'        => 'hire',
            'new_branch_id'      => $data->branch_id,
            'new_department_id'  => $data->department_id,
            'new_job_title_id'   => $data->job_title_id,
            'new_status'         => $data->status->value,
            'new_employment_type' => $data->employment_type->value,
            'effective_date'     => $data->hired_at ?? now()->toDateString(),
        ]);

        event(new EmployeeCreated($employee));

        return $employee;
    }
}
