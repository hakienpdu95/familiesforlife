<?php

namespace Modules\RealEstate\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\RealEstate\Models\RealEstateListing;
use Modules\RealEstate\Policies\RealEstateListingPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class RealEstateServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'RealEstate';
    protected string $nameLower = 'realestate';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(RealEstateListing::class, RealEstateListingPolicy::class);
    }
}
