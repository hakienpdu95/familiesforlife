<?php

namespace Modules\RealEstate\Features\ListingManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/real-estate — xem RealEstateListingApiController. */
class RealEstateListingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $approvalStatus = $this->approvalStatus();

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'listing_type_label'  => $this->listing_type->label(),
            'property_type_label' => $this->property_type->label(),
            'display_price' => $this->display_price,

            'approval_label' => $approvalStatus?->label(),
            'approval_badge' => $approvalStatus?->badgeClass(),

            'created_at' => $this->created_at?->format('d/m/Y'),

            'edit_url'    => route('backend.real-estate.edit', $this->resource),
            'destroy_url' => route('backend.real-estate.destroy', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
