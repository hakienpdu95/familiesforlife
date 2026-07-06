<?php

namespace Modules\Lead\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\LeadActivityData;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadActivity;

class LogLeadActivityAction
{
    use AsAction;

    public function handle(LeadActivityData $data): LeadActivity
    {
        $activity = LeadActivity::create([
            'lead_id'          => $data->leadId,
            'organization_id'  => $data->orgId,
            'type'             => $data->type,
            'title'            => $data->title,
            'description'      => $data->description,
            'outcome'          => $data->outcome,
            'scheduled_at'     => $data->scheduledAt,
            'completed_at'     => $data->completedAt,
            'duration_minutes' => $data->durationMinutes,
            'attendee_count'   => $data->attendeeCount,
            'actor_id'         => $data->actorId,
            'actor_name'       => $data->actorName,
            'created_at'       => now(),
        ]);

        // Counter cache update — skip global scope (action runs in queue context too)
        Lead::withoutGlobalScopes()
            ->where('id', $data->leadId)
            ->update([
                'last_activity_at' => now(),
                'activity_count'   => \Illuminate\Support\Facades\DB::raw('activity_count + 1'),
            ]);

        return $activity;
    }
}
