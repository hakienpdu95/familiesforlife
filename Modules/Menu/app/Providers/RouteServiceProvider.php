<?php

namespace Modules\Menu\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Menu';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * Routes quản trị (dashboard/menu-items) — chưa cần route công khai ở Phase 1
     * (spec/Menu_Navigation_Technical_Specification.md §8: render công khai là Phase 3).
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
