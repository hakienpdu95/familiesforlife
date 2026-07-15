<?php

namespace Modules\Banner\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Banner';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * routes/web.php gồm cả nhóm admin (dashboard/banners, middleware 'auth') lẫn route công
     * khai (banners/{banner}/click — đếm click, không yêu cầu đăng nhập) — cùng 1 file, tách
     * bằng Route::middleware()->group() bên trong, giống Modules/Post routes/web.php.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
