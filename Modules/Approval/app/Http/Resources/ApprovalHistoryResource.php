<?php

namespace Modules\Approval\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Approval\Enums\ApprovalStatus;

class ApprovalHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $entity = $this->subject?->subject;
        $from = $this->from_status ? ApprovalStatus::tryFrom($this->from_status) : null;
        $to = ApprovalStatus::tryFrom($this->to_status);

        return [
            'id' => $this->id,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'entity_label' => $entity ? class_basename($entity).' #'.$entity->id : null,
            'entity_name' => $entity->name ?? null,
            'entity_url' => $entity?->approvalDashboardUrl ?? null,
            'type_label' => $this->subject ? config("approval.subjects.{$this->subject->subject_type}.label", $this->subject->subject_type) : null,
            'action_label' => $this->actionLabel(),
            'from_status_label' => $this->from_status ? ($from?->label() ?? $this->from_status) : null,
            'to_status_label' => $to?->label() ?? $this->to_status,
            'to_status_badge' => $to?->badgeClass(),
            'performed_by_name' => $this->performedBy?->name,
            'reason' => $this->reason,
        ];
    }
}
