<?php

namespace Modules\Lead\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id'                  => $this->id,
            'title'               => $this->displayTitle(),
            'contact_name'        => $this->contact_name,
            'contact_phone'       => $this->contact_phone,
            'contact_company'     => $this->contact_company,
            'stage_id'            => $this->stage_id,
            'stage_label'         => $this->stage?->label,
            'stage_color'         => $this->stage?->color,
            'source_label'        => $this->source?->label,
            'source_icon'         => $this->source?->icon,
            'assignee_name'       => $this->assignee?->name,
            'expected_value'      => $this->expected_value,
            'currency'            => $this->currency,
            'expected_close_date' => $this->expected_close_date?->format('Y-m-d'),
            'lead_score'          => $this->lead_score,
            'status_value'        => $status?->value,
            'status_label'        => $status?->label(),
            'status_badge'        => $status?->badgeClass(),
            'last_activity_at'    => $this->last_activity_at?->format('d/m/Y'),
            'activity_count'      => $this->activity_count,
            'created_at'          => $this->created_at?->format('d/m/Y'),
            'tags'                => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($t) => [
                'id'    => $t->id,
                'name'  => $t->name,
                'color' => $t->color,
            ])),
            'show_url'            => route('lead.show', $this->resource),
            'edit_url'            => route('lead.edit', $this->resource),
            'delete_url'          => route('lead.destroy', $this->resource),
        ];
    }
}
