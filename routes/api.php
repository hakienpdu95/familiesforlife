<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserOptionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Middleware group: api (EnsureFrontendRequestsAreStateful prepended)
| → auth:sanctum works with session cookie from browser AJAX calls.
| Auto-prefix: /api  (set by withRouting api: in bootstrap/app.php)
*/

Route::middleware(['auth:sanctum', 'tenant'])
    ->get('v1/users/options', UserOptionsController::class)
    ->name('api.users.options');

Route::middleware(['auth:sanctum', 'tenant', 'throttle:notifications'])
    ->prefix('notifications')
    ->name('api.notifications.')
    ->group(function () {
        Route::get('/',             [NotificationController::class, 'index'])       ->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']) ->name('unread-count');
        Route::patch('/{uuid}/read',[NotificationController::class, 'markRead'])    ->name('mark-read');
        Route::post('/read-all',    [NotificationController::class, 'markAllRead']) ->name('read-all');
        Route::delete('/{uuid}',    [NotificationController::class, 'destroy'])     ->name('destroy');

        // Notification preferences
        Route::get('/preferences',                   [NotificationController::class, 'preferences'])      ->name('preferences');
        Route::patch('/preferences/{eventType}',     [NotificationController::class, 'updatePreference']) ->name('preferences.update');

        // Browser Push subscriptions
        Route::post('/push-subscribe',    [NotificationController::class, 'pushSubscribe'])
            ->withoutMiddleware('throttle:notifications')
            ->middleware('throttle:push-subscribe')
            ->name('push-subscribe');
        Route::delete('/push-unsubscribe',[NotificationController::class, 'pushUnsubscribe'])
            ->name('push-unsubscribe');
    });
