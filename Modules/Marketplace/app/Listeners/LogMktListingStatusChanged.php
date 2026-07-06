<?php

namespace Modules\Marketplace\Listeners;

use Modules\Marketplace\Events\MktListingStatusChanged;

class LogMktListingStatusChanged
{
    public function handle(MktListingStatusChanged $event): void
    {
        activity()->on($event->listing)->log('mkt_listing.status_changed:' . $event->newStatus->value);
    }
}
