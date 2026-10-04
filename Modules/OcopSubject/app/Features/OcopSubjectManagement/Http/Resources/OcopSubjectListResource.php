<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Ocop\Models\OcopProduct;

class OcopSubjectListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'tax_code' => $this->tax_code,
            'organization_type' => $this->organization_type->label(),
            'province_name' => $this->province_name,
            'ocop_star' => $this->ocop_star,
            'products_count' => (int) $this->products_count,
            'is_active' => (bool) $this->is_active,

            'edit_url' => route('backend.ocop-subjects.edit', $this->resource),
            'destroy_url' => route('backend.ocop-subjects.destroy', $this->resource),
            'create_product_url' => route('backend.ocop.products.create', ['subject_id' => $this->id]),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
            'can_create_product' => $this->is_active && ($user?->can('create', OcopProduct::class) ?? false),
        ];
    }
}
