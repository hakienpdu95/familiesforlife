<?php

use App\Http\Controllers\Api\MediaJoditUploadController;
use App\Http\Controllers\Api\MediaUploadController;
use App\Http\Controllers\Backend\Api\DashboardChartController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\SystemMonitorController;
use App\Http\Controllers\Backend\SystemSettingController;
use App\Http\Controllers\Backend\NotificationCenterController;
use App\Http\Controllers\Backend\NotificationPreferenceController;
use App\Http\Controllers\Backend\QrReportController;
use App\Http\Controllers\TraceQrController;
use Illuminate\Support\Facades\Route;

// Trang chủ công khai '/' đăng ký trong Modules/Post/routes/web.php (cùng chỗ với
// post.public.home) — render thẳng tại '/', không redirect sang /{locale}/bai-viet.

Route::get('p/{qr_code}', TraceQrController::class)
    ->where('qr_code', '[0-9A-Za-z]{6,15}')
    ->middleware('throttle:60,1')
    ->name('traceability.qr');

Route::get('p/{qr_code}/ho-so/{media}', [TraceQrController::class, 'document'])
    ->where(['qr_code' => '[0-9A-Za-z]{6,15}', 'media' => '[0-9a-f\-]{36}'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('traceability.document');

Route::post('p/{qr_code}/report', [TraceQrController::class, 'report'])
    ->where('qr_code', '[0-9A-Za-z]{6,15}')
    ->middleware('throttle:5,10')
    ->name('traceability.report');

/*
|--------------------------------------------------------------------------
| Media API Routes — prefix: api/v1/media
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->prefix('api/v1/media')
    ->name('api.media.')
    ->group(function () {
            // Jodit inline-image upload (orphan + uuid tracking)
        Route::post('jodit-upload',         [MediaJoditUploadController::class, 'store'])->name('jodit.upload');
        Route::delete('jodit-upload/{uuid}',[MediaJoditUploadController::class, 'destroy'])->name('jodit.destroy');
        Route::post('jodit-discard',        [MediaJoditUploadController::class, 'discard'])->name('jodit.discard');
        Route::patch('jodit-touch',         [MediaJoditUploadController::class, 'touch'])->name('jodit.touch');
        Route::get('{uuid}/url',            [MediaJoditUploadController::class, 'refreshUrl'])->name('url.refresh');

        // FilePond form-field upload (avatar, logo, thumbnail, cover, attachments)
        Route::post('upload',         [MediaUploadController::class, 'store'])->name('upload');
        Route::delete('upload/{uuid}',[MediaUploadController::class, 'destroy'])->name('upload.destroy');
        Route::patch('upload/chunk/{id}', [MediaUploadController::class, 'chunkAppend'])->whereUuid('id')->name('upload.chunk');
        Route::match(['HEAD'], 'upload/chunk/{id}', [MediaUploadController::class, 'chunkOffset'])->whereUuid('id')->name('upload.chunk.offset');
    });

/*
|--------------------------------------------------------------------------
| Backend Routes — prefix: backend.*
|--------------------------------------------------------------------------
| User CRUD          → Modules/User/routes/web.php
*/
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('qr-reports', [QrReportController::class, 'index'])->name('qr-reports.index');
    Route::get('qr-reports/data', [QrReportController::class, 'data'])->name('qr-reports.data');
    Route::post('qr-reports/{report}/resolve', [QrReportController::class, 'resolve'])->name('qr-reports.resolve');

    Route::get('settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SystemSettingController::class, 'update'])->name('settings.update');

    Route::get('system-monitor', [SystemMonitorController::class, 'index'])->name('system-monitor');
    Route::get('system-monitor/data', [SystemMonitorController::class, 'data'])->name('system-monitor.data');

    // ── Dashboard chart API ───────────────────────────────────────────────
    Route::prefix('api/dashboard/charts')->name('dashboard.charts.')->group(function () {
        Route::get('content-trend',   [DashboardChartController::class, 'contentTrend'])  ->name('content-trend');
    });

    // ── Placeholder routes (modules chưa triển khai) ──────────────────
    // products.index/products.create giờ do Modules/Product/routes/web.php đảm nhiệm
    Route::get('/orders',           fn () => abort(503, 'Module đang phát triển'))->name('orders.index');
    Route::get('/categories',       fn () => abort(503, 'Module đang phát triển'))->name('categories.index');
    Route::get('/categories/create',fn () => abort(503, 'Module đang phát triển'))->name('categories.create');

    // ── Notification Center ───────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',             [NotificationCenterController::class,    'index'])       ->name('index');
        Route::get('/preferences',  [NotificationPreferenceController::class,'index'])       ->name('preferences');
        Route::patch('/{uuid}/read',[NotificationCenterController::class,    'markRead'])    ->name('mark-read');
        Route::post('/read-all',    [NotificationCenterController::class,    'markAllRead']) ->name('read-all');
        Route::delete('/{uuid}',    [NotificationCenterController::class,    'destroy'])     ->name('destroy');
    });

});
