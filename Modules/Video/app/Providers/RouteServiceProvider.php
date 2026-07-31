<?php

namespace Modules\Video\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Video';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * routes/web.php gồm cả nhóm admin (dashboard/videos, middleware 'auth') lẫn route công
     * khai (videos — trang liệt kê, không yêu cầu đăng nhập) — cùng 1 file, tách bằng
     * Route::middleware()->group() bên trong, giống Modules/Banner routes/web.php.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
