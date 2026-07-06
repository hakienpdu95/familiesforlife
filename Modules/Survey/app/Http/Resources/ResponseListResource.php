<?php

namespace Modules\Survey\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponseListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'respondent_ref' => $this->respondent_ref,
            'respondent_ip'  => $this->respondent_ip, // decoded by SurveyResponse::respondentIp() accessor
            'status_value'   => $this->status->value,
            'status_label'   => $this->status->label(),
            'status_badge'   => $this->status->badgeClass(),
            'submitted_at'   => $this->submitted_at?->format('d/m/Y H:i'),
            'show_url'       => route('backend.surveys.responses.show', [
                'survey'   => $this->survey_id,
                'response' => $this->id,
            ]),
            'delete_url'     => route('backend.surveys.responses.destroy', [
                'survey'   => $this->survey_id,
                'response' => $this->id,
            ]),
        ];
    }
}
