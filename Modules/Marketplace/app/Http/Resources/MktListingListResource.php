<?php

namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Enums\ListingStatus;

class MktListingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'title'            => $this->title,
            'listing_type'     => $this->listing_type instanceof \Modules\Marketplace\Enums\ListingType
                                    ? $this->listing_type->value : $this->listing_type,
            'listing_type_label' => $this->listing_type instanceof \Modules\Marketplace\Enums\ListingType
                                    ? $this->listing_type->label() : $this->listing_type,
            'poster_type'      => $this->poster_type instanceof \Modules\Marketplace\Enums\PosterType
                                    ? $this->poster_type->value : $this->poster_type,
            'status'           => $status instanceof ListingStatus ? $status->value : $status,
            'status_label'     => $status instanceof ListingStatus ? $status->label() : $status,
            'status_badge'     => $status instanceof ListingStatus ? $status->badgeClass() : 'badge-ghost',
            'work_type'        => $this->work_type instanceof \Modules\Marketplace\Enums\WorkType
                                    ? $this->work_type->label() : $this->work_type,
            'location'         => $this->location,
            'application_count' => $this->application_count,
            'view_count'       => $this->view_count,
            'jp_sync_status'   => $this->jp_sync_status instanceof \Modules\Marketplace\Enums\JpSyncStatus
                                    ? $this->jp_sync_status->value : $this->jp_sync_status,
            'org_name'         => $this->organization?->name ?? 'Cá nhân',
            'posted_by_name'   => $this->postedBy?->name,
            'expire_at'        => $this->expire_at?->format('d/m/Y'),
            'created_at'       => $this->created_at?->format('d/m/Y'),
            'show_url'         => route('backend.marketplace.listings.show', $this->resource),
            'edit_url'         => route('backend.marketplace.listings.edit', $this->resource),
            'close_url'        => route('backend.marketplace.listings.close', $this->resource),
        ];
    }
}
