<?php

namespace Modules\Leave\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Leave\Data\Requests\UpdateLeavePolicyData;
use Modules\Leave\Models\LeavePolicy;

class UpdateLeavePolicyAction
{
    use AsAction;

    public function handle(LeavePolicy $policy, UpdateLeavePolicyData $data): LeavePolicy
    {
        $policy->update([
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
        ]);

        return $policy->fresh();
    }
}
