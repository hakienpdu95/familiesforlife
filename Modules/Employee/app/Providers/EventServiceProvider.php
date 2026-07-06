<?php

namespace Modules\Employee\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Employee\Events\EmployeeCreated;
use Modules\Employee\Events\EmployeeUpdated;
use Modules\Employee\Listeners\LogEmployeeCreated;
use Modules\Employee\Listeners\LogEmployeeUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        EmployeeCreated::class => [LogEmployeeCreated::class],
        EmployeeUpdated::class => [LogEmployeeUpdated::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
