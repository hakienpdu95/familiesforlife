<?php

namespace Modules\Ocop\Features\OcopProductManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Ocop\Enums\OcopProductStatus;

/** Tabulator row cho dashboard/ocop/products — xem OcopProductApiController. */
class OcopProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $trashed = $this->trashed();

        return [
            'id' => $this->id,
            'image_url' => $this->imageUrl('thumb'),
            'name' => $this->name,
            'is_featured' => (bool) $this->is_featured,
            'category' => $this->category?->name,
            'star_rating' => $this->star_rating,
            'province_name' => $this->province_name,

            'status_value' => $this->status->value,
            'status_label' => $this->status->label(),

            'qr_code' => $this->qrIdentity?->qr_code,
            'qr_url' => $this->qrIdentity?->publicUrl(),
            'edit_url' => route('backend.ocop.products.edit', $this->resource),
            'destroy_url' => route('backend.ocop.products.destroy', $this->resource),

            'can_update' => ! $trashed && ($user?->can('update', $this->resource) ?? false),
            'can_delete' => ! $trashed && ($user?->can('delete', $this->resource) ?? false),

            'is_trashed' => $trashed,
            'is_public' => $this->status === OcopProductStatus::Published,
            'deleted_at' => $this->deleted_at?->format('d/m/Y H:i'),
            'restore_url' => $trashed && ($user?->can('restore', $this->resource) ?? false) ? route('backend.ocop.products.restore', $this->resource) : null,
        ];
    }
}
