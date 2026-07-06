<?php

namespace Modules\Marketplace\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Marketplace\Events\MktListingCreated;
use Modules\Marketplace\Events\MktListingStatusChanged;
use Modules\Marketplace\Events\MktListingUpdated;
use Modules\Marketplace\Listeners\LogMktListingCreated;
use Modules\Marketplace\Listeners\LogMktListingStatusChanged;
use Modules\Marketplace\Listeners\LogMktListingUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MktListingCreated::class       => [LogMktListingCreated::class],
        MktListingUpdated::class       => [LogMktListingUpdated::class],
        MktListingStatusChanged::class => [LogMktListingStatusChanged::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
