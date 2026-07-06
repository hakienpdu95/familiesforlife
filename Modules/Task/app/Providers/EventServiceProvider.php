<?php

namespace Modules\Task\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;
use Modules\Task\Listeners\LogTaskCreated;
use Modules\Task\Listeners\LogTaskUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TaskCreated::class => [LogTaskCreated::class],
        TaskUpdated::class => [LogTaskUpdated::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
