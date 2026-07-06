<?php

namespace Modules\Department\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Department\Data\Requests\UpdateDepartmentData;
use Modules\Department\Events\DepartmentUpdated;
use Modules\Department\Models\Department;

class UpdateDepartmentAction
{
    use AsAction;

    public function handle(Department $dept, UpdateDepartmentData $data): Department
    {
        $dept->update([
            'parent_id'       => $data->parent_id,
            'branch_id'       => $data->branch_id,
            'name'            => $data->name,
            'code'            => strtoupper(trim($data->code)),
            'function'        => $data->function?->value,
            'status'          => $data->status->value,
            'merged_into_id'  => $data->merged_into_id,
            'budget_code'     => $data->budget_code,
            'headcount_limit' => $data->headcount_limit,
            'description'     => $data->description,
            'internal_phone'  => $data->internal_phone,
            'internal_email'  => $data->internal_email,
            'effective_from'  => $data->effective_from,
            'effective_to'    => $data->effective_to,
            'updated_by'      => auth()->id(),
        ]);

        event(new DepartmentUpdated($dept));

        return $dept;
    }
}
