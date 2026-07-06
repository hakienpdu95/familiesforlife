<?php

use Modules\ActivityLog\Http\Controllers\ActivityLogApiController;
use Modules\ActivityLog\Http\Controllers\ActivityLogController;
Route::prefix('dashboard/activity-logs')
    ->middleware(['web', 'auth', 'can:activitylog.view'])
    ->name('activitylog.')
    ->group(function () {
        Route::get('/',                      [ActivityLogController::class, 'index'])          ->name('index');
        Route::get('/{log}',                 [ActivityLogController::class, 'show'])           ->name('show');
        Route::post('/export',               [ActivityLogController::class, 'export'])         ->name('export');
        Route::get('/export/download/{key}', [ActivityLogController::class, 'downloadExport'])->name('export.download');
    });

Route::prefix('backend/api/activity-logs')
    ->middleware(['web', 'auth', 'can:activitylog.view'])
    ->name('backend.api.activitylog.')
    ->group(function () {
        Route::get('/',      [ActivityLogApiController::class, 'index']) ->name('index');
        Route::get('/stats', [ActivityLogApiController::class, 'stats']) ->name('stats');
        Route::get('/meta',  [ActivityLogApiController::class, 'meta'])  ->name('meta');
    });
