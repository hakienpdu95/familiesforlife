<?php

namespace Modules\Employee\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\UpdateEmployeeData;
use Modules\Employee\Events\EmployeeUpdated;
use Modules\Employee\Models\Employee;

class UpdateEmployeeAction
{
    use AsAction;

    public function handle(Employee $employee, UpdateEmployeeData $data): Employee
    {
        $employee->update([
            'user_id'                   => $data->user_id,
            'branch_id'                 => $data->branch_id,
            'department_id'             => $data->department_id,
            'job_title_id'              => $data->job_title_id,
            'manager_id'                => $data->manager_id,
            'employee_code'             => strtoupper(trim($data->employee_code)),
            'full_name'                 => $data->full_name,
            'email'                     => $data->email,
            'personal_email'            => $data->personal_email,
            'phone'                     => $data->phone,
            'gender'                    => $data->gender,
            'date_of_birth'             => $data->date_of_birth,
            'national_id'               => $data->national_id,
            'national_id_issued'        => $data->national_id_issued,
            'tax_code'                  => $data->tax_code,
            'locale'                    => $data->locale,
            'avatar_url'                => $data->avatar_url,
            'status'                    => $data->status->value,
            'employment_type'           => $data->employment_type->value,
            'hired_at'                  => $data->hired_at,
            'probation_end_date'        => $data->probation_end_date,
            'contract_start'            => $data->contract_start,
            'contract_end'              => $data->contract_end,
            'work_location'             => $data->work_location,
            'address'                   => $data->address,
            'emergency_contact_name'    => $data->emergency_contact_name,
            'emergency_contact_phone'   => $data->emergency_contact_phone,
            'updated_by'                => auth()->id(),
        ]);

        event(new EmployeeUpdated($employee));

        return $employee;
    }
}
