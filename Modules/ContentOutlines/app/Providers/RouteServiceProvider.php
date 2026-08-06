<?php

namespace Modules\ContentOutlines\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'ContentOutlines';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * routes/web.php gồm cả trang quản trị (dashboard/content-outlines) lẫn JSON backend cho
     * Tabulator (backend/api/content-outlines/...) — cùng 1 file, cả 2 dùng session auth (không
     * cần routes/api.php riêng — module này không có endpoint public/stateless nào, khác N8n).
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
