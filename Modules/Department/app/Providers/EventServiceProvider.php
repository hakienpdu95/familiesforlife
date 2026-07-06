<?php

namespace Modules\Department\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Department\Events\DepartmentCreated::class => [
            \Modules\Department\Listeners\LogDepartmentCreated::class,
        ],
        \Modules\Department\Events\DepartmentUpdated::class => [
            \Modules\Department\Listeners\LogDepartmentUpdated::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
