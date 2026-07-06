<?php

namespace Modules\Marketplace\Listeners;

use Modules\Marketplace\Events\MktListingUpdated;

class LogMktListingUpdated
{
    public function handle(MktListingUpdated $event): void
    {
        activity()->on($event->listing)->log('mkt_listing.updated');
    }
}
