<?php

namespace Modules\AccessTrade\Features\OfferManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/accesstrade/offers — xem OfferApiController. */
class OfferListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'image_url'    => $this->image,
            'name'         => $this->name,
            'merchant'     => $this->merchant,
            'domain'       => $this->domain,
            'has_coupon'   => (bool) $this->has_coupon,
            'coupon_count' => count($this->coupons ?? []),
            'aff_link'     => $this->aff_link,
            'link'         => $this->link,
            'start_time'   => $this->start_time?->format('d/m/Y'),
            'end_time'     => $this->end_time?->format('d/m/Y'),
            'last_synced_at' => $this->last_synced_at?->format('d/m/Y H:i'),
        ];
    }
}
