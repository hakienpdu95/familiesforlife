<?php

namespace Modules\KcCategory\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\KcCategory\Models\KcCategory;
use Modules\KcCategory\Policies\KcCategoryPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class KcCategoryServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'KcCategory';

    protected string $nameLower = 'kccategory';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(KcCategory::class, KcCategoryPolicy::class);
    }
}
