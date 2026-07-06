<?php

namespace Modules\Branch\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type   = $this->type;
        $status = $this->status;

        return [
            'id'   => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,

            'type'       => $type->value,
            'type_label' => $type->label(),
            'type_badge' => $type->badgeClass(),

            'status'       => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'depth'     => $this->depth,
            'parent_id' => $this->parent_id,
            'parent_name' => $this->parent ? ($this->parent->name . ' (' . $this->parent->code . ')') : null,

            'province_name'  => $this->province?->name,
            'address'        => $this->address,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'tax_code'       => $this->tax_code,

            'children_count' => $this->children_count,

            'opened_at'  => $this->opened_at?->format('d/m/Y'),
            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.branches.show', $this->resource),
            'edit_url'   => route('backend.branches.edit', $this->resource),
            'delete_url' => route('backend.branches.destroy', $this->resource),

            'can_delete' => $this->children_count === 0 && $status->value !== 'active',
        ];
    }
}
