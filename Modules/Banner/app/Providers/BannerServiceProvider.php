<?php

namespace Modules\Banner\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Banner\Models\Banner;
use Modules\Banner\Policies\BannerPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class BannerServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Banner';
    protected string $nameLower = 'banner';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Banner::class, BannerPolicy::class);
    }
}
