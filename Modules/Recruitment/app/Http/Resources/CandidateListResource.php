<?php

namespace Modules\Recruitment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $source = $this->source;

        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'full_name'         => $this->full_name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'current_title'     => $this->current_title,
            'current_company'   => $this->current_company,
            'years_experience'  => $this->years_experience,
            'status'            => $status?->value ?? $status,
            'status_label'      => $status?->label() ?? $status,
            'source'            => $source?->value ?? $source,
            'source_label'      => $source?->label() ?? $source,
            'applications_count' => $this->applications_count ?? 0,
            'created_at'        => $this->created_at?->format('d/m/Y'),
            'show_url'          => route('backend.recruitment.candidates.show', $this->id),
            'edit_url'          => route('backend.recruitment.candidates.edit', $this->id),
        ];
    }
}
