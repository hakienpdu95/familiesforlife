<?php

namespace App\Providers;

use App\Models\Media;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Routing\LocalizedUrlGenerator;
use App\Services\Monitoring\MetricsRecorder;
use App\Services\WebPushService;
use App\Shared\Traceability\Jobs\RefreshQrSnapshotJob;
use App\Shared\Traceability\TraceabilityRegistry;
use App\Shared\Traceability\Traits\HasQrIdentity;
use App\Support\I18n\PortalLocale;
use App\Support\SystemLocale;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetricsRecorder::class);
        $this->app->scoped(PortalLocale::class);
        $this->registerLocalizedUrlGenerator();
        $this->app->singleton(TraceabilityRegistry::class);

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
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            $portal = $this->app->make(PortalLocale::class);
            $prefix = $event->request->attributes->get(PortalLocale::PREFIX_ATTRIBUTE);

            if ($portal->isPortalRoute($event->route)) {
                $portal->activate($prefix ?? PortalLocale::default());
                if ($prefix !== null) {
                    Paginator::currentPathResolver(fn () => $this->app['url']->current());
                }
            } elseif ($prefix !== null) {
                abort(404);
            }
        });

        $refreshQrSnapshot = function (Media $media): void {
            $class = Relation::getMorphedModel($media->model_type) ?? $media->model_type;

            if (class_exists($class) && in_array(HasQrIdentity::class, class_uses_recursive($class), true)) {
                RefreshQrSnapshotJob::queueFor($class, $media->model_id);
            }
        };
        Media::saved($refreshQrSnapshot);
        Media::deleted($refreshQrSnapshot);

        Model::shouldBeStrict(! app()->isProduction());

        Queue::before(fn () => SystemLocale::apply());

        if ($this->app->runningInConsole()) {
            SystemLocale::apply();
        }

        Gate::define('manageSystemSettings', fn (User $user) => $user->hasGlobalRole('super-admin'));

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
        Gate::define('manageQrReports', fn (User $user) => $user->hasGlobalRole('super-admin') || $user->isPlatformContentModerator() || $user->isPlatformContentHead() || $user->isPlatformOps());
        Gate::define('viewSystemMonitor', fn (User $user) => $user->hasGlobalRole('super-admin') || $user->isPlatformOps());

        if (config('monitoring.enabled') && ! $this->app->runningInConsole()) {
            DB::listen(fn (QueryExecuted $query) => $this->app->make(MetricsRecorder::class)
                ->recordQuery($query->sql, $query->time, $query->connectionName));
        }

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            $subject = $arguments[0] ?? null;

            if ($subject instanceof User || $subject === User::class) {
                return null;
            }

            return $user->hasRole('super-admin') ? true : null;
        });

        // Global API limiter — 120 req/min per authenticated user, 30/min for guests
        RateLimiter::for('api', fn (Request $request) => $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip())
        );

        RateLimiter::for('notifications', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('push-subscribe', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        // Register custom 'webpush' notification channel
        $this->app->make(ChannelManager::class)
            ->extend('webpush', fn ($app) => new WebPushChannel($app->make(WebPushService::class)));
    }

    private function registerLocalizedUrlGenerator(): void
    {
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes();
            if (! $app->bound('routes')) {
                $app->instance('routes', $routes);
            }

            return new LocalizedUrlGenerator(
                $routes,
                $app->rebinding('request', fn ($app, $request) => $app['url']->setRequest($request)),
                $app['config']['app.asset_url'],
            );
        });
    }
}
