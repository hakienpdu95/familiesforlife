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
use Illuminate\Support\Facades\RateLimiter;
use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Foundation\Vertical\VerticalTemplate;
use App\Policies\VerticalTemplatePolicy;
use Modules\Assessment\Models\PassportEntry;
use Modules\Assessment\Policies\PassportEntryPolicy;

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

        // super-admin bypass toàn bộ Gate checks
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Passport policies
        Gate::policy(PassportEntry::class, PassportEntryPolicy::class);

        // Vertical templates (thư viện mẫu — System Admin)
        Gate::policy(VerticalTemplate::class, VerticalTemplatePolicy::class);

        // Sidebar: load active verticals for current org
        View::composer('layouts.partials.sidebar', SidebarComposer::class);

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
