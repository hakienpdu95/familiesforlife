<?php

namespace Modules\RoleScope\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\RoleScope\Events\RoleScopeGranted;
use Modules\RoleScope\Events\RoleScopeRevoked;
use Modules\RoleScope\Listeners\LogRoleScopeGranted;
use Modules\RoleScope\Listeners\LogRoleScopeRevoked;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        RoleScopeGranted::class => [
            LogRoleScopeGranted::class,
        ],
        RoleScopeRevoked::class => [
            LogRoleScopeRevoked::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
