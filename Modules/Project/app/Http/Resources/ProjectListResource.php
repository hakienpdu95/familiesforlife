<?php

namespace Modules\Project\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status   = $this->status;
        $priority = $this->priority;

        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'code'        => $this->code,
            'name'        => $this->name,
            'description' => $this->description,
            'category'    => $this->category,

            'status'       => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'priority'       => $priority->value,
            'priority_label' => $priority->label(),
            'priority_badge' => $priority->badgeClass(),

            'branch_name' => $this->branch?->name,
            'dept_name'   => $this->department?->name,
            'owner_name'  => $this->owner?->full_name,
            'owner_code'  => $this->owner?->employee_code,

            'active_members_count' => $this->active_members_count ?? 0,

            'start_date' => $this->start_date?->format('d/m/Y'),
            'end_date'   => $this->end_date?->format('d/m/Y'),
            'budget'     => $this->budget,
            'currency'   => $this->currency,
            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.projects.show', $this->resource),
            'edit_url'   => route('backend.projects.edit', $this->resource),
            'delete_url' => route('backend.projects.destroy', $this->resource),
        ];
    }
}
