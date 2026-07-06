<?php

namespace Modules\Department\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Department\Data\Requests\StoreDepartmentData;
use Modules\Department\Events\DepartmentCreated;
use Modules\Department\Models\Department;

class StoreDepartmentAction
{
    use AsAction;

    public function handle(StoreDepartmentData $data): Department
    {
        $dept = Department::create([
            'uuid'            => Str::uuid(),
            'organization_id' => $data->organization_id,
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
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
        ]);

        event(new DepartmentCreated($dept));

        return $dept;
    }
}
