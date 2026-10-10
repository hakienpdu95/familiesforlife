<?php

namespace Modules\Approval\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $entity = $this->subject;

        return [
            'id' => $this->id,
            'subject_type' => $this->subject_type,
            'type_label' => config("approval.subjects.{$this->subject_type}.label", $this->subject_type),
            'entity_label' => class_basename($entity).' #'.$entity->id,
            'name' => $entity->name ?? null,
            'organization_name' => $entity->relationLoaded('organization') ? $entity->organization?->name : null,
            'submitted_at' => $this->updated_at?->toIso8601String(),
            'submitted_at_label' => $this->updated_at?->format('d/m/Y H:i'),
            'submitted_ago' => $this->updated_at?->locale('vi')->diffForHumans(),
            'review_url' => $entity->approvalDashboardUrl ?? null,
        ];
    }
}
