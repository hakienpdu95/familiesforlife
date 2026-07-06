<?php

namespace Modules\OrgChart\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\OrgChart\Events\OrgChartConfigCreated;
use Modules\OrgChart\Events\OrgChartConfigUpdated;
use Modules\OrgChart\Listeners\LogOrgChartConfigCreated;
use Modules\OrgChart\Listeners\LogOrgChartConfigUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrgChartConfigCreated::class => [LogOrgChartConfigCreated::class],
        OrgChartConfigUpdated::class => [LogOrgChartConfigUpdated::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
