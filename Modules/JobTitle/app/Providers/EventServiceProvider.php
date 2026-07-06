<?php

namespace Modules\JobTitle\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\JobTitle\Events\JobTitleCreated;
use Modules\JobTitle\Events\JobTitleUpdated;
use Modules\JobTitle\Listeners\LogJobTitleCreated;
use Modules\JobTitle\Listeners\LogJobTitleUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JobTitleCreated::class => [
            LogJobTitleCreated::class,
        ],
        JobTitleUpdated::class => [
            LogJobTitleUpdated::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
