<?php

namespace Modules\Event\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Event';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        // spec/Event_Management_Technical_Specification.md §8/§10.2 — 5 request/giờ/IP, thấp
        // hơn survey-submit (60/phút theo token) vì sự kiện không cần submit nhiều lần liên
        // tục như khảo sát; kết hợp Turnstile (§10.1) là đủ cho quy mô 1 form submit sự kiện.
        RateLimiter::for('event-submit', function (Request $request) {
            return Limit::perHour(5)->by('event_submit_' . $request->ip());
        });

        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }
}
