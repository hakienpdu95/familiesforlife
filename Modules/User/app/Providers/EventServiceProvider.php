<?php

namespace Modules\User\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\User\Events\UserCreated;
use Modules\User\Events\UserRoleAssigned;
use Modules\User\Listeners\LogUserCreated;
use Modules\User\Listeners\LogUserRoleAssigned;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserCreated::class      => [LogUserCreated::class],
        UserRoleAssigned::class => [LogUserRoleAssigned::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
