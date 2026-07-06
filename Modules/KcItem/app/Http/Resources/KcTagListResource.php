<?php

namespace Modules\KcItem\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KcTagListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'color_hex'   => $this->color_hex,
            'items_count' => (int) ($this->items_count ?? 0),
            'created_at'  => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.kc-tags.show', $this->resource),
            'edit_url'   => route('backend.kc-tags.edit', $this->resource),
            'delete_url' => route('backend.kc-tags.destroy', $this->resource),
        ];
    }
}
