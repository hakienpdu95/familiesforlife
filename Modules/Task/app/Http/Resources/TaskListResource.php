<?php

namespace Modules\Task\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type     = $this->task_type;
        $status   = $this->status;
        $priority = $this->priority;

        return [
            'id'   => $this->id,
            'uuid' => $this->uuid,

            'title'            => $this->title,
            'task_type'        => $type->value,
            'task_type_label'  => $type->label(),
            'task_type_badge'  => $type->badgeClass(),
            'task_type_icon'   => $type->icon(),

            'status'       => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'priority'       => $priority->value,
            'priority_label' => $priority->label(),
            'priority_badge' => $priority->badgeClass(),

            'due_date'    => $this->due_date?->format('d/m/Y'),
            'start_date'  => $this->start_date?->format('d/m/Y'),
            'completed_at'=> $this->completed_at?->format('d/m/Y H:i'),

            'story_points'     => $this->story_points,
            'estimated_hours'  => $this->estimated_hours,
            'logged_hours'     => $this->logged_hours,
            'progress_pct'     => $this->progress_pct,
            'subtask_total'    => $this->subtask_total,
            'subtask_done'     => $this->subtask_done,
            'comment_count'    => $this->comment_count,
            'attachment_count' => $this->attachment_count,
            'depth'            => $this->depth,
            'is_leaf'          => $this->is_leaf,
            'is_archived'      => $this->is_archived,

            'employee_name' => $this->employee?->full_name,
            'project_name'  => $this->project?->name,
            'project_code'  => $this->project?->code,

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.tasks.show', $this->resource),
            'edit_url'   => route('backend.tasks.edit', $this->resource),
            'delete_url' => route('backend.tasks.destroy', $this->resource),
        ];
    }
}
