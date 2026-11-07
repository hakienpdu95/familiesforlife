<?php

namespace Modules\Ocop\Providers;

use App\Shared\Traceability\TraceabilityRegistry;
use Illuminate\Support\Facades\Gate;
use Modules\Ocop\Features\Traceability\OcopProductTracePresenter;
use Modules\Ocop\Models\OcopCategory;
use Modules\Ocop\Models\OcopProduct;
use Modules\Ocop\Policies\OcopCategoryPolicy;
use Modules\Ocop\Policies\OcopProductPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OcopServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Ocop';
    protected string $nameLower = 'ocop';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(OcopCategory::class, OcopCategoryPolicy::class);
        Gate::policy(OcopProduct::class, OcopProductPolicy::class);

        $this->app->make(TraceabilityRegistry::class)->register(OcopProduct::class, OcopProductTracePresenter::class);
    }
}
