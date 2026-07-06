<?php

namespace Modules\OrgChart\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\OrgChart\Models\OrgChartConfig;
use Modules\OrgChart\Policies\OrgChartConfigPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OrgChartServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'OrgChart';

    protected string $nameLower = 'orgchart';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(OrgChartConfig::class, OrgChartConfigPolicy::class);
    }
}
