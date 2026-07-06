<?php

namespace Modules\Lead\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\LeadActivityData;
use Modules\Lead\Data\Requests\UpdateLeadData;
use Modules\Lead\Enums\LeadActivityType;
use Modules\Lead\Events\LeadUpdated;
use Modules\Lead\Models\Lead;

class UpdateLeadAction
{
    use AsAction;

    public function handle(Lead $lead, UpdateLeadData $data): Lead
    {
        $updatedLead = DB::transaction(function () use ($lead, $data) {

            $stageChanged = $lead->stage_id !== $data->stage_id;

            $lead->update([
                'stage_id'            => $data->stage_id,
                'stage_changed_at'    => $stageChanged ? now() : $lead->stage_changed_at,
                'source_id'           => $data->source_id,
                'source_detail'       => $data->source_detail,
                'assigned_to'         => $data->assigned_to,
                'assigned_at'         => ($data->assigned_to && $data->assigned_to !== $lead->assigned_to)
                    ? now()
                    : $lead->assigned_at,
                'expected_value'      => $data->expected_value,
                'currency'            => $data->currency,
                'expected_close_date' => $data->expected_close_date,
                'title'               => $data->title,
                'description'         => $data->description,
                'updated_by'          => Auth::id(),
            ]);

            // If stage changed, delegate to ChangeLeadStageAction for full history
            if ($stageChanged) {
                ChangeLeadStageAction::run($lead->fresh(), $data->stage_id);
            }

            // Log update activity
            LogLeadActivityAction::run(new LeadActivityData(
                leadId:      $lead->id,
                orgId:       $lead->organization_id,
                type:        LeadActivityType::System->value,
                title:       'Cập nhật thông tin lead',
                completedAt: now()->toDateTimeString(),
                actorId:     Auth::id(),
                actorName:   Auth::user()?->name,
            ));

            return $lead->fresh();
        });

        // Fire domain event after transaction commits
        event(new LeadUpdated($updatedLead));

        // Async re-scoring
        ScoreLeadAction::dispatchForLead($updatedLead->id);

        return $updatedLead;
    }
}
