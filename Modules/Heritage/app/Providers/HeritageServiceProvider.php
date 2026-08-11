<?php

namespace Modules\Heritage\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Heritage\Models\HeritageSite;
use Modules\Heritage\Policies\HeritageSitePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HeritageServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Heritage';

    protected string $nameLower = 'heritage';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(HeritageSite::class, HeritageSitePolicy::class);
    }
}
