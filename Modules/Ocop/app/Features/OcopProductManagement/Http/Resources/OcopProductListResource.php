<?php

namespace Modules\Ocop\Features\OcopProductManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/ocop/products — xem OcopProductApiController. */
class OcopProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'          => $this->id,
            'image_url'   => $this->getFirstMediaUrl('cover', 'thumb'),
            'name'        => $this->name,
            'is_featured' => (bool) $this->is_featured,
            'category'    => $this->category?->name,
            'star_rating' => $this->star_rating,
            'province_name' => $this->province_name,

            'status_value' => $this->status->value,
            'status_label' => $this->status->label(),

            'edit_url'    => route('backend.ocop.products.edit', $this->resource),
            'destroy_url' => route('backend.ocop.products.destroy', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
