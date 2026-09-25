<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ProvinceShowcase\Enums\ProvincePlaceType;
use Modules\ProvinceShowcase\Support\ProvinceTabColor;

class ProvinceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $placeType = ProvincePlaceType::tryFrom((string) $this->place_type);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'slug' => $this->slug,
            'province_code' => $this->province_code,
            'place_type' => $this->place_type,
            'place_type_label' => $placeType?->label(),
            'place_type_badge' => $placeType?->badgeClass(),
            'region' => $this->region?->name,
            'is_active' => (bool) $this->is_active,
            'theme_color' => ProvinceTabColor::resolve($this->theme_color),
            'is_featured' => (bool) $this->is_featured,
            'order_column' => $this->order_column,
            'edit_url' => route('backend.provinces.edit', $this->resource),
            'can_update' => $request->user()?->can('update', $this->resource) ?? false,
            'public_url' => $this->slug
                ? route('province.public.show', ['type' => $this->place_type, 'slug' => $this->slug])
                : null,
        ];
    }
}
