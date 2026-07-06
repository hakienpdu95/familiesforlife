<?php

namespace Modules\Marketplace\Listeners;

use Modules\Marketplace\Events\MktListingCreated;

class LogMktListingCreated
{
    public function handle(MktListingCreated $event): void
    {
        activity()->on($event->listing)->log('mkt_listing.created');
    }
}
