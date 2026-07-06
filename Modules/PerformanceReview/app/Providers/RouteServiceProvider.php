<?php

namespace Modules\PerformanceReview\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\PerformanceReview\Models\PerformanceReview;
use Modules\PerformanceReview\Models\ReviewTemplate;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'PerformanceReview';

    public function boot(): void
    {
        parent::boot();

        // Bypass TenantScope for route model binding
        Route::bind('performance_review', function ($value) {
            return PerformanceReview::withoutTenant()->findOrFail($value);
        });

        Route::bind('review_template', function ($value) {
            return ReviewTemplate::withoutTenant()->findOrFail($value);
        });
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }
}
