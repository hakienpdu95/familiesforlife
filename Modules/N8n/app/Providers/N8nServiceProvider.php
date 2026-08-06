<?php

namespace Modules\N8n\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Modules\N8n\Features\OutboundDelivery\Services\N8nOutboundService;
use Modules\N8n\Models\N8nConnection;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/N8n_Integration_Technical_Specification.md §6/§5.8 — đăng ký named rate limiter
 * (resolve theo connection TRƯỚC khi vào Controller), bind Facade, Gate::define('manage-n8n').
 */
class N8nServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'N8n';

    protected string $nameLower = 'n8n';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // config/n8n.php tự merge qua ModuleServiceProvider::registerConfig() (chạy ở boot()
        // của parent, theo tên file = nameLower) — không cần mergeConfigFrom() thủ công ở đây.
        $this->app->singleton(N8nOutboundService::class);
    }

    public function boot(): void
    {
        parent::boot();

        // §6 — chỉ platform_ops được tạo/sửa/tắt/xoay secret; super-admin tự bypass qua
        // Gate::before (app/Providers/AppServiceProvider.php).
        Gate::define('manage-n8n', fn ($user) => $user->isPlatformOps());

        // §5.8 — tự tra N8nConnection theo $request->route('token') NGAY TRONG closure (route
        // model binding không dùng ở đây vì cần trả response generic thay vì 404 mặc định của
        // Laravel khi không tìm thấy).
        RateLimiter::for('n8n-inbound', function (Request $request) {
            $connection = N8nConnection::where('inbound_token', $request->route('token'))->first();
            $limit = $connection?->rate_limit_per_minute ?? config('n8n.default_rate_limit_per_minute');

            return Limit::perMinute($limit)->by($request->route('token'));
        });
    }
}
