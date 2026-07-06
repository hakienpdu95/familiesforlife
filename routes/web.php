<?php

use App\Http\Controllers\Api\MediaJoditUploadController;
use App\Http\Controllers\Api\MediaUploadController;
use App\Http\Controllers\Backend\Api\DashboardChartController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\NotificationCenterController;
use App\Http\Controllers\Backend\NotificationPreferenceController;
use App\Http\Controllers\Backend\VerticalChecklistItemController;
use App\Http\Controllers\Backend\VerticalPhaseController;
use App\Http\Controllers\Backend\VerticalTemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('backend.dashboard'));

/*
|--------------------------------------------------------------------------
| Media API Routes — prefix: api/v1/media
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant'])
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
    });

/*
|--------------------------------------------------------------------------
| Backend Routes — prefix: backend.*
|--------------------------------------------------------------------------
| Organization CRUD  → Modules/Organization/routes/web.php
| User CRUD          → Modules/User/routes/web.php
*/
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Dashboard chart API ───────────────────────────────────────────────
    Route::prefix('api/dashboard/charts')->name('dashboard.charts.')->middleware('tenant')->group(function () {
        Route::get('task-throughput', [DashboardChartController::class, 'taskThroughput'])->name('task-throughput');
        Route::get('lead-funnel',     [DashboardChartController::class, 'leadFunnel'])    ->name('lead-funnel');
        Route::get('workflow-health', [DashboardChartController::class, 'workflowHealth'])->name('workflow-health');
        Route::get('headcount',       [DashboardChartController::class, 'headcount'])     ->name('headcount');
    });

    // ── Placeholder routes (modules chưa triển khai) ──────────────────
    Route::get('/products',         fn () => abort(503, 'Module đang phát triển'))->name('products.index');
    Route::get('/products/create',  fn () => abort(503, 'Module đang phát triển'))->name('products.create');
    Route::get('/orders',           fn () => abort(503, 'Module đang phát triển'))->name('orders.index');
    Route::get('/customers',        fn () => abort(503, 'Module đang phát triển'))->name('customers.index');
    Route::get('/customers/create', fn () => abort(503, 'Module đang phát triển'))->name('customers.create');
    Route::get('/categories',       fn () => abort(503, 'Module đang phát triển'))->name('categories.index');
    Route::get('/categories/create',fn () => abort(503, 'Module đang phát triển'))->name('categories.create');
    Route::get('/settings',         fn () => abort(503, 'Module đang phát triển'))->name('settings.index');
    Route::get('/reports',          fn () => abort(503, 'Module đang phát triển'))->name('reports.index');

    // ── Notification Center ───────────────────────────────────────────────
    Route::middleware('tenant')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',             [NotificationCenterController::class,    'index'])       ->name('index');
        Route::get('/preferences',  [NotificationPreferenceController::class,'index'])       ->name('preferences');
        Route::patch('/{uuid}/read',[NotificationCenterController::class,    'markRead'])    ->name('mark-read');
        Route::post('/read-all',    [NotificationCenterController::class,    'markAllRead']) ->name('read-all');
        Route::delete('/{uuid}',    [NotificationCenterController::class,    'destroy'])     ->name('destroy');
    });

    // ── Vertical templates — thư viện mẫu (System Admin) ──────────────────
    Route::resource('vertical-templates', VerticalTemplateController::class)->except(['show']);
    Route::get('vertical-templates/survey-options', [VerticalTemplateController::class, 'surveyOptions'])
        ->name('vertical-templates.survey-options');

    Route::prefix('vertical-templates/{vertical_template}/phases')
        ->name('vertical-templates.phases.')
        ->group(function () {
            Route::post('/',        [VerticalPhaseController::class, 'store'])->name('store');
            Route::put('/{phase}',  [VerticalPhaseController::class, 'update'])->name('update');
            Route::delete('/{phase}', [VerticalPhaseController::class, 'destroy'])->name('destroy');
            Route::patch('/reorder', [VerticalPhaseController::class, 'reorder'])->name('reorder');

            Route::prefix('/{phase}/checklist-items')
                ->name('checklist-items.')
                ->group(function () {
                    Route::post('/',       [VerticalChecklistItemController::class, 'store'])->name('store');
                    Route::put('/{item}',  [VerticalChecklistItemController::class, 'update'])->name('update');
                    Route::delete('/{item}', [VerticalChecklistItemController::class, 'destroy'])->name('destroy');
                    Route::patch('/reorder', [VerticalChecklistItemController::class, 'reorder'])->name('reorder');
                });
        });

});
