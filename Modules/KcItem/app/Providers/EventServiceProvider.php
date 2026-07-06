<?php

namespace Modules\KcItem\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\KcItem\Events\KcItemCreated;
use Modules\KcItem\Events\KcItemUpdated;
use Modules\KcItem\Listeners\LogKcItemCreated;
use Modules\KcItem\Listeners\LogKcItemUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        KcItemCreated::class => [
            LogKcItemCreated::class,
        ],
        KcItemUpdated::class => [
            LogKcItemUpdated::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
