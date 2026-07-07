<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Enums\ProductLinkType;

/** @mixin \Modules\Product\Models\Product */
class ProductPickerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $availableLinks = collect(ProductLinkType::cases())
            ->filter(fn (ProductLinkType $type) => filled($this->{$type->urlColumn()}))
            ->map(fn (ProductLinkType $type) => [
                'type'  => $type->value,
                'label' => $type->label(),
            ])
            ->values();

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'short_description' => $this->short_description,
            'price_label'       => $this->display_price,
            'cover_image_url'   => $this->cover_image_url,
            'status'            => $this->status->value,
            'available_links'   => $availableLinks,
        ];
    }
}
