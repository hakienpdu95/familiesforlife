<?php

namespace Modules\Organization\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Organization\Events\OrganizationCreated;
use Modules\Organization\Events\OrganizationUpdated;
use Modules\Organization\Listeners\LogOrganizationCreated;
use Modules\Organization\Listeners\LogOrganizationUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrganizationCreated::class => [
            LogOrganizationCreated::class,
        ],
        OrganizationUpdated::class => [
            LogOrganizationUpdated::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
