<?php

namespace Modules\Project\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Project\Events\ProjectCreated;
use Modules\Project\Events\ProjectUpdated;
use Modules\Project\Listeners\LogProjectCreated;
use Modules\Project\Listeners\LogProjectUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProjectCreated::class => [LogProjectCreated::class],
        ProjectUpdated::class => [LogProjectUpdated::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
