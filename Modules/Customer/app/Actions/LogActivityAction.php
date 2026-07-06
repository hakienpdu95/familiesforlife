<?php

namespace Modules\Customer\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Data\CustomerActivityData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerActivity;

class LogActivityAction
{
    use AsAction;

    public function handle(CustomerActivityData $data): CustomerActivity
    {
        $activity = CustomerActivity::create([
            'customer_id'      => $data->customerId,
            'organization_id'  => $data->orgId,
            'lead_id'          => $data->leadId,
            'type'             => $data->type,
            'title'            => $data->title,
            'description'      => $data->description,
            'outcome'          => $data->outcome,
            'scheduled_at'     => $data->scheduledAt,
            'completed_at'     => $data->completedAt,
            'duration_minutes' => $data->durationMinutes,
            'actor_id'         => $data->actorId,
            'actor_name'       => $data->actorName,
            'created_at'       => now(),
        ]);

        Customer::withoutGlobalScopes()
            ->where('id', $data->customerId)
            ->update([
                'last_activity_at' => now(),
                'activity_count'   => DB::raw('activity_count + 1'),
            ]);

        return $activity;
    }
}
