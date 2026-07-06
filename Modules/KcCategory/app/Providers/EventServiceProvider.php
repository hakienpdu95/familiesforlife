<?php

namespace Modules\KcCategory\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\KcCategory\Events\KcCategoryCreated;
use Modules\KcCategory\Events\KcCategoryUpdated;
use Modules\KcCategory\Listeners\LogKcCategoryCreated;
use Modules\KcCategory\Listeners\LogKcCategoryUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        KcCategoryCreated::class => [
            LogKcCategoryCreated::class,
        ],
        KcCategoryUpdated::class => [
            LogKcCategoryUpdated::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
