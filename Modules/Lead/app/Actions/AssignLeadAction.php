<?php

namespace Modules\Lead\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Data\LeadActivityData;
use Modules\Lead\Enums\LeadActivityType;
use Modules\Lead\Events\LeadAssigned;
use Modules\Lead\Models\Lead;

class AssignLeadAction
{
    use AsAction;

    public function handle(Lead $lead, ?int $userId): Lead
    {
        $oldAssignee = $lead->assigned_to;
        if ($oldAssignee === $userId) return $lead;

        $assigneeName = $userId
            ? (User::find($userId)?->name ?? "User #{$userId}")
            : null;

        $lead->update([
            'assigned_to' => $userId,
            'assigned_at' => $userId ? now() : null,
            'updated_by'  => Auth::id(),
        ]);

        $title = $userId
            ? "Phân công cho: {$assigneeName}"
            : 'Hủy phân công';

        LogLeadActivityAction::run(new LeadActivityData(
            leadId:      $lead->id,
            orgId:       $lead->organization_id,
            type:        LeadActivityType::Assign->value,
            title:       $title,
            completedAt: now()->toDateTimeString(),
            actorId:     Auth::id(),
            actorName:   Auth::user()?->name,
        ));

        $updatedLead = $lead->fresh();

        // Fire domain event
        event(new LeadAssigned($updatedLead, $oldAssignee, $userId));

        return $updatedLead;
    }
}
