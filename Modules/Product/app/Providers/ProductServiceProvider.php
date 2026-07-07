<?php

namespace Modules\Product\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Product\Contracts\ProductCatalogContract;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use Modules\Product\Policies\ProductCategoryPolicy;
use Modules\Product\Policies\ProductPolicy;
use Modules\Product\Services\ProductCatalogService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProductServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Product';
    protected string $nameLower = 'product';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(ProductCatalogContract::class, ProductCatalogService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
    }
}
