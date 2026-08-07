<?php

namespace Modules\PromptFrameworkStudio\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'PromptFrameworkStudio';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * routes/web.php gồm cả trang quản trị (dashboard/prompt-studio) lẫn JSON backend cho
     * Tabulator (backend/api/prompt-studio/...) — cùng 1 file, cả 2 dùng session auth (không cần
     * routes/api.php riêng) — cùng mẫu Modules/ContentOutlines/app/Providers/RouteServiceProvider.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
