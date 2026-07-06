<?php

namespace Modules\Recruitment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $source = $this->apply_source;
        $stage  = $this->currentStage;

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'status'           => $status?->value ?? $status,
            'status_label'     => $status?->label() ?? $status,
            'apply_source'     => $source?->value ?? $source,
            'source_label'     => $source?->label() ?? $source,
            'is_disqualified'  => $this->is_disqualified,
            'applied_at'       => $this->applied_at?->format('d/m/Y'),
            'candidate_name'   => $this->candidate?->full_name,
            'candidate_email'  => $this->candidate?->email,
            'candidate_title'  => $this->candidate?->current_title,
            'stage_name'       => $stage?->name,
            'stage_color'      => $stage?->color_hex,
            'assigned_to'      => $this->assignedTo?->name,
            'show_url'         => route('backend.recruitment.applications.show', $this->id),
        ];
    }
}
