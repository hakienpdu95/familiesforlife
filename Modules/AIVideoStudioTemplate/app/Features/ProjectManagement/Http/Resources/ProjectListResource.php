<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;

/** Tabulator row cho dashboard/ai-video-studio — xem ProjectApiController. */
class ProjectListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,

            'status' => $this->status,
            'status_label' => __(AiVideoStudioProject::STATUSES[$this->status] ?? $this->status),
            'status_badge' => match ($this->status) {
                'active' => 'badge-success',
                'archived' => 'badge-ghost',
                default => 'badge-warning',
            },

            'shots_count' => $this->shots_count,

            'created_by_name' => $this->createdBy?->name,
            'created_at' => format_datetime($this->created_at),

            'show_url' => route('backend.aivideostudiotemplate.show', $this->resource),
            'edit_url' => route('backend.aivideostudiotemplate.edit', $this->resource),
            'destroy_url' => route('backend.aivideostudiotemplate.destroy', $this->resource),
        ];
    }
}
