<?php

namespace Modules\Product\Features\CatalogManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/products — xem ProductApiController. */
class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $approvalStatus = $this->approvalStatus();

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'sku'         => $this->sku,
            'category'    => $this->category?->name,
            'type_value'  => $this->type->value,
            'type_label'  => $this->type->label(),
            'display_price' => $this->display_price,

            'status_value' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_badge' => $this->status->badgeClass(),

            'approval_label' => $approvalStatus?->label(),
            'approval_badge' => $approvalStatus?->badgeClass(),

            'used_in_articles_count' => (int) $this->used_in_articles_count,

            'edit_url'    => route('backend.products.edit', $this->resource),
            'destroy_url' => route('backend.products.destroy', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
