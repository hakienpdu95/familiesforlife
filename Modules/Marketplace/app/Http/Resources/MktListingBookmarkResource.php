<?php
namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktListingBookmarkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->uuid,
            'note'       => $this->note,
            'created_at' => $this->created_at?->toISOString(),
            'listing'    => $this->whenLoaded('listing', fn() => [
                'id'              => $this->listing->uuid,
                'title'           => $this->listing->title,
                'slug'            => $this->listing->slug,
                'listing_type'    => $this->listing->listing_type?->value,
                'work_type'       => $this->listing->work_type?->value,
                'employment_type' => $this->listing->employment_type?->value,
                'status'          => $this->listing->status?->value,
                'org_name'        => $this->listing->organization?->name ?? 'Cá nhân',
            ]),
        ];
    }
}
