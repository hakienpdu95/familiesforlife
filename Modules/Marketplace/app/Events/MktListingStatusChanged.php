<?php

namespace Modules\Marketplace\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Models\MktListing;

class MktListingStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MktListing $listing,
        public readonly ListingStatus $newStatus,
    ) {}
}
