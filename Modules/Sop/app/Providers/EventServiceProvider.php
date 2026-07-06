<?php

namespace Modules\Sop\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Sop\Events\SopProcessCreated;
use Modules\Sop\Events\SopProcessUpdated;
use Modules\Sop\Listeners\LogSopProcessCreated;
use Modules\Sop\Listeners\LogSopProcessUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SopProcessCreated::class => [LogSopProcessCreated::class],
        SopProcessUpdated::class => [LogSopProcessUpdated::class],
    ];

    protected static $shouldDiscoverEvents = false;
}
