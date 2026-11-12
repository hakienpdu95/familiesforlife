<?php

use App\Http\Middleware\ApplySystemLocale;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RecordRequestMetrics;
use App\Http\Middleware\RemoveServerHeaders;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StripPortalLocalePrefix;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\ActivityLog\Http\Middleware\CaptureHttpContext;
use Modules\ActivityLog\Http\Middleware\InjectRequestId;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->redirectUsersTo(fn () => route('backend.dashboard'));
        // InjectRequestId phải chạy đầu tiên để tất cả request đều có X-Request-Id
        $middleware->prepend(StripPortalLocalePrefix::class);
        $middleware->prepend(RemoveServerHeaders::class);
        $middleware->append(RecordRequestMetrics::class);
        $middleware->prepend(InjectRequestId::class);
        $middleware->appendToGroup('web', ApplySystemLocale::class);
        $middleware->appendToGroup('web', CaptureHttpContext::class);
        $middleware->appendToGroup('web', SecurityHeaders::class);
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
        // EnsureFrontendRequestsAreStateful phải đứng đầu api group để auth:sanctum
        // có thể dùng session cookie từ browser (SPA/Tabulator AJAX calls).
        // Không có middleware này, sanctum chỉ nhận Bearer token → 401.
        $middleware->prependToGroup('api', EnsureFrontendRequestsAreStateful::class);
        $middleware->appendToGroup('api', ThrottleRequests::class.':api');
        $middleware->appendToGroup('api', ApplySystemLocale::class);
        $middleware->appendToGroup('api', CaptureHttpContext::class);
        $middleware->appendToGroup('api', EnsureUserIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
