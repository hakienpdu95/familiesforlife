<?php

namespace Modules\AccessTrade\Features\TopProductManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/accesstrade/top-products — xem TopProductApiController. */
class TopProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'image_url'      => $this->image,
            'name'           => $this->name,
            'merchant'       => $this->merchant ?: null,
            'brand'          => $this->brand,
            'category_name'  => $this->category_name,
            'price'          => (float) $this->price,
            'discount'       => (float) $this->discount,
            'total'          => (int) $this->total,
            'aff_link'       => $this->aff_link,
            'link'           => $this->link,
            'last_synced_at' => $this->last_synced_at?->format('d/m/Y H:i'),
        ];
    }
}
