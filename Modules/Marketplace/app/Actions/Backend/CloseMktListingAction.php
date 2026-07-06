<?php

namespace Modules\Marketplace\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Events\MktListingStatusChanged;
use Modules\Marketplace\Models\MktListing;

class CloseMktListingAction
{
    use AsAction;

    public function handle(MktListing $listing): MktListing
    {
        $listing->update([
            'status'    => ListingStatus::CLOSED->value,
            'closed_at' => now(),
        ]);

        event(new MktListingStatusChanged($listing, ListingStatus::CLOSED));

        return $listing;
    }
}
