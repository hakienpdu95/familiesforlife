<?php

namespace Modules\Survey\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Survey\Enums\SurveyStatus;

class SurveyListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'slug'            => $this->slug,
            'version'         => $this->version,
            'status'          => $this->status->value,
            'status_label'    => $this->status->label(),
            'status_badge'    => $this->status->badgeClass(),
            'responses_count' => $this->responses_count,
            'created_at'      => $this->created_at?->format('d/m/Y'),
            'can_delete'      => $this->status !== SurveyStatus::Active,
            'edit_url'        => route('backend.surveys.edit', $this->resource),
            'stats_url'       => route('backend.surveys.stats.index', $this->resource),
            'responses_url'   => route('backend.surveys.responses.index', $this->resource),
            'delete_url'      => route('backend.surveys.destroy', $this->resource),
            'take_url'        => route('backend.surveys.take', $this->slug),
        ];
    }
}
