<?php

namespace Modules\Playlist\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Playlist';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * routes/web.php gồm cả nhóm admin (dashboard/playlists, middleware 'auth'), API nội bộ cho
     * Tabulator/picker, lẫn route công khai (playlists — không yêu cầu đăng nhập) — cùng 1 file,
     * tách bằng Route::middleware()->group() bên trong, giống Modules/Video routes/web.php.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
