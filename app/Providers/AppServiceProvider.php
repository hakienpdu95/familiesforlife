<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Services\WebPushService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Đăng ký 3 thư mục migration con để `php artisan migrate` luôn phát hiện được
        // (Laravel glob chỉ scan 1 cấp — không đệ quy — nên cần đăng ký tường minh).
        // migration:generate --fresh vẫn dùng --path= riêng, không bị ảnh hưởng.
        $this->loadMigrationsFrom([
            database_path('migrations/vendor'),
            database_path('migrations/generated'),
            database_path('migrations/extensions'),
        ]);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(!app()->isProduction());

        if (app()->isProduction()) {
            DB::disableQueryLog();
        }

        // spec/SiteSearch_Activation_Expansion_Technical_Specification.md §4.5 — giải pháp TẠM
        // THỜI (log critical, không chặn hẳn) vì repo chưa có pipeline CI/CD nào để chèn
        // `php artisan scout:verify-driver` (app/Console/Commands/VerifyScoutDriverCommand.php)
        // làm bước chặn deploy. Nâng cấp lên chặn deploy ở CI/CD ngay khi có pipeline — đây
        // KHÔNG phải giải pháp cuối cùng.
        if (app()->environment('production', 'staging') && config('scout.driver') !== 'meilisearch') {
            Log::critical('[Scout] SCOUT_DRIVER cấu hình sai cho môi trường '.app()->environment().
                " (hiện là '".config('scout.driver')."', cần 'meilisearch') — search sẽ âm thầm chạy driver 'collection', mất typo-tolerance/relevance-ranking. Xem spec/SiteSearch_Activation_Expansion_Technical_Specification.md §4.5.");
        }

        // super-admin bypass toàn bộ Gate checks
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Global API limiter — 120 req/min per authenticated user, 30/min for guests
        RateLimiter::for('api', fn (Request $request) =>
            $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip())
        );

        RateLimiter::for('notifications', fn (Request $request) =>
            Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('push-subscribe', fn (Request $request) =>
            Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        // Register custom 'webpush' notification channel
        $this->app->make(ChannelManager::class)
            ->extend('webpush', fn ($app) => new WebPushChannel($app->make(WebPushService::class)));
    }
}
