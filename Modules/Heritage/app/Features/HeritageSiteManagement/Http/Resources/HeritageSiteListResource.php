<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/heritage/sites — xem HeritageSiteApiController. */
class HeritageSiteListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'image_url' => $this->getFirstMediaUrl('cover', 'thumb'),
            'name' => $this->name,
            'is_featured' => (bool) $this->is_featured,
            'heritage_type_label' => $this->heritage_type->label(),
            'rank_label' => $this->rank->label(),
            'province_name' => $this->province_name,

            'status_value' => $this->status->value,
            'status_label' => $this->status->label(),

            'edit_url' => route('backend.heritage.sites.edit', $this->resource),
            'destroy_url' => route('backend.heritage.sites.destroy', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
