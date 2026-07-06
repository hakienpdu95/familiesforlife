<?php

namespace Modules\Department\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status   = $this->status;
        $function = $this->function;

        return [
            'id'   => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,

            'status'       => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'function'       => $function?->value,
            'function_label' => $function?->label(),
            'function_badge' => $function?->badgeClass(),

            'depth'       => $this->depth,
            'parent_id'   => $this->parent_id,
            'parent_name' => $this->parent ? ($this->parent->name . ' (' . $this->parent->code . ')') : null,

            'branch_id'   => $this->branch_id,
            'branch_name' => $this->branch ? ($this->branch->name . ' (' . $this->branch->code . ')') : null,

            'budget_code'      => $this->budget_code,
            'headcount_limit'  => $this->headcount_limit,
            'internal_phone'   => $this->internal_phone,
            'internal_email'   => $this->internal_email,

            'children_count' => $this->children_count,

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.departments.show', $this->resource),
            'edit_url'   => route('backend.departments.edit', $this->resource),
            'delete_url' => route('backend.departments.destroy', $this->resource),

            'can_delete' => $this->children_count === 0 && $status->value !== 'active',
        ];
    }
}
