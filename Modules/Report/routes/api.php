<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\Api\HrReportApiController;
use Modules\Report\Http\Controllers\Api\SalesReportApiController;

Route::middleware(['auth:sanctum', 'tenant'])
    ->prefix('v1/report')
    ->name('api.report.')
    ->group(function () {

        // ── HR ──────────────────────────────────────────────────────
        Route::middleware('can:reports.hr,reports.full')->group(function () {
            Route::get('/hr/headcount',   [HrReportApiController::class, 'headcount']  )->name('hr.headcount');
        });

        // ── Sales ────────────────────────────────────────────────────
        Route::middleware('can:reports.team,reports.personal,reports.full')->group(function () {
            Route::get('/sales/pipeline',   [SalesReportApiController::class, 'pipeline']  )->name('sales.pipeline');
            Route::get('/sales/conversion', [SalesReportApiController::class, 'conversion'])->name('sales.conversion');
            Route::get('/sales/activity',   [SalesReportApiController::class, 'activity']  )->name('sales.activity');
        });
    });
