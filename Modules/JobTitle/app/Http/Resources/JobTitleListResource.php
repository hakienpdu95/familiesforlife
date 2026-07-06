<?php

namespace Modules\JobTitle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobTitleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->category;

        return [
            'id'   => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,

            'category'       => $category->value,
            'category_label' => $category->label(),
            'category_badge' => $category->badgeClass(),

            'level'     => $this->level,
            'is_system' => (bool) $this->is_system,
            'is_locked' => (bool) $this->is_locked,
            'is_active' => (bool) $this->is_active,

            'description' => $this->description,
            'created_at'  => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.job-titles.show', $this->resource),
            'edit_url'   => route('backend.job-titles.edit', $this->resource),
            'delete_url' => route('backend.job-titles.destroy', $this->resource),

            'can_edit'   => ! $this->is_locked,
            'can_delete' => ! $this->is_locked,
        ];
    }
}
