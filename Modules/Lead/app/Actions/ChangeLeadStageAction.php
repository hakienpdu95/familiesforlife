<?php

namespace Modules\Lead\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\LeadActivityData;
use Modules\Lead\Enums\LeadActivityType;
use Modules\Lead\Enums\LeadStatus;
use Modules\Lead\Events\LeadStageChanged;
use Modules\Lead\Models\Lead;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;
use Modules\Lead\Models\LeadStageHistory;

class ChangeLeadStageAction
{
    use AsAction;

    public function handle(Lead $lead, int $newStageId, ?string $note = null): Lead
    {
        $oldStageId = $lead->stage_id;
        if ($oldStageId === $newStageId) return $lead;

        $updatedLead = DB::transaction(function () use ($lead, $newStageId, $oldStageId, $note) {

            $oldStage = LeadPipelineStage::find($oldStageId);
            $newStage = LeadPipelineStage::findOrFail($newStageId);

            // Derive new status from stage flags
            $newStatus = match (true) {
                $newStage->is_won  => LeadStatus::Converted,
                $newStage->is_lost => LeadStatus::Archived,
                default            => $lead->status,
            };

            $lead->update([
                'stage_id'          => $newStageId,
                'stage_changed_at'  => now(),
                'status'            => $newStatus,
                'actual_close_date' => ($newStage->is_won || $newStage->is_lost)
                    ? now()->toDateString()
                    : $lead->actual_close_date,
                'updated_by'        => Auth::id(),
            ]);

            // Stage history record
            LeadStageHistory::create([
                'lead_id'          => $lead->id,
                'organization_id'  => $lead->organization_id,
                'stage_from_id'    => $oldStageId,
                'stage_to_id'      => $newStageId,
                'stage_from_label' => $oldStage?->label,
                'stage_to_label'   => $newStage->label,
                'changed_by'       => Auth::id(),
                'changed_by_name'  => Auth::user()?->name,
                'note'             => $note,
                'changed_at'       => now(),
                'created_at'       => now(),
            ]);

            // Activity log
            LogLeadActivityAction::run(new LeadActivityData(
                leadId:      $lead->id,
                orgId:       $lead->organization_id,
                type:        LeadActivityType::StageChange->value,
                title:       sprintf(
                    'Đổi tình trạng: %s → %s',
                    $oldStage?->label ?? 'Không rõ',
                    $newStage->label
                ),
                description: $note,
                completedAt: now()->toDateTimeString(),
                actorId:     Auth::id(),
                actorName:   Auth::user()?->name,
            ));

            return $lead->fresh();
        });

        // Fire domain event after transaction commits
        event(new LeadStageChanged($updatedLead, $oldStageId, $newStageId));

        return $updatedLead;
    }
}
