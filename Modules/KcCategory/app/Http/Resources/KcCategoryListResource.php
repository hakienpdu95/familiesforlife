<?php

namespace Modules\KcCategory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KcCategoryListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'icon'           => $this->icon,
            'color_hex'      => $this->color_hex,
            'sort_order'     => $this->sort_order,
            'is_active'      => (bool) $this->is_active,
            'children_count' => (int) $this->children_count,
            'description'    => $this->description,

            'parent' => $this->parent ? [
                'id'   => $this->parent->id,
                'name' => $this->parent->name,
            ] : null,

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.kc-categories.show', $this->resource),
            'edit_url'   => route('backend.kc-categories.edit', $this->resource),
            'delete_url' => route('backend.kc-categories.destroy', $this->resource),

            'can_delete' => $this->children_count === 0,
        ];
    }
}
