<?php

namespace Modules\N8n\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'N8n';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * routes/web.php gồm cả admin CRUD kết nối/log (dashboard/n8n, middleware 'auth', KHÔNG
     * 'tenant' — §6) lẫn JSON backend cho Tabulator (backend/api/n8n/..., session-based auth,
     * cùng guard trang quản trị — cùng pattern Modules/Video, KHÔNG đặt trong api.php vì cần
     * session/CSRF, khác route inbound webhook).
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * routes/api.php CHỈ chứa 1 route: inbound webhook công khai (§5.1) — server-to-server,
     * KHÔNG session/CSRF, bảo mật hoàn toàn dựa vào token định tuyến + HMAC. KHÔNG ->name('api.')
     * prefix (khác Modules/Event) — spec yêu cầu tên route chính xác `n8n.inbound` (§5.1/§9).
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->group(module_path($this->name, '/routes/api.php'));
    }
}
