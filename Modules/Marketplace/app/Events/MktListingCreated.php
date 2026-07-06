<?php

namespace Modules\Marketplace\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Marketplace\Models\MktListing;

class MktListingCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly MktListing $listing) {}
}
