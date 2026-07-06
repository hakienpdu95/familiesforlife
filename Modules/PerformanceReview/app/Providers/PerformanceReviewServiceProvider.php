<?php

namespace Modules\PerformanceReview\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\PerformanceReview\Models\PerformanceReview;
use Modules\PerformanceReview\Policies\PerformanceReviewPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PerformanceReviewServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'PerformanceReview';

    protected string $nameLower = 'performancereview';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(PerformanceReview::class, PerformanceReviewPolicy::class);
    }
}
