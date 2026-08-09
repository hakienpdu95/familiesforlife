<?php

namespace Modules\AIVideoStudioTemplate\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'AIVideoStudioTemplate';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * routes/web.php gồm cả trang quản trị (dashboard/ai-video-studio) lẫn JSON inline cho quản lý
     * Shot (backend/api/ai-video-studio/...) — cùng 1 file, cả 2 dùng session auth (§6 spec).
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
