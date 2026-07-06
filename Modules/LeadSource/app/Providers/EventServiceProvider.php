<?php

namespace Modules\LeadSource\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\LeadSource\Events\SourceCreated;
use Modules\LeadSource\Events\SourceDeleted;
use Modules\LeadSource\Events\SourceUpdated;
use Modules\LeadSource\Listeners\FlushSourcesCache;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SourceCreated::class => [FlushSourcesCache::class],
        SourceUpdated::class => [FlushSourcesCache::class],
        SourceDeleted::class => [FlushSourcesCache::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
