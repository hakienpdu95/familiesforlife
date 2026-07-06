<?php

namespace Modules\Leave\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Leave\Data\Requests\StoreLeavePolicyData;
use Modules\Leave\Models\LeavePolicy;

class StoreLeavePolicyAction
{
    use AsAction;

    public function handle(StoreLeavePolicyData $data): LeavePolicy
    {
        return LeavePolicy::create([
            'uuid'                 => Str::uuid(),
            'organization_id'      => $data->organization_id,
            'leave_type'           => $data->leave_type->value,
            'name'                 => $data->name,
            'days_per_year'        => $data->days_per_year,
            'carry_over_days'      => $data->carry_over_days,
            'min_advance_days'     => $data->min_advance_days,
            'max_consecutive_days' => $data->max_consecutive_days,
            'requires_approval'    => $data->requires_approval,
            'job_title_id'         => $data->job_title_id,
            'department_id'        => $data->department_id,
            'effective_from'       => $data->effective_from,
            'is_active'            => $data->is_active,
            'created_by'           => auth()->id(),
        ]);
    }
}
