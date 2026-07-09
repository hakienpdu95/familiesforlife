<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\ReportDashboardController;
use Modules\Report\Http\Controllers\SalesReportController;

Route::middleware(['auth', 'verified', 'tenant'])
    ->prefix('report')
    ->name('report.')
    ->group(function () {

        Route::get('/', [ReportDashboardController::class, 'index'])->name('index');

        // ── Sales ────────────────────────────────────────────────────
        Route::middleware('can:reports.team,reports.personal,reports.full')
            ->prefix('sales')->name('sales.')
            ->group(function () {
                Route::get('/',           [SalesReportController::class, 'index']      )->name('index');
                Route::get('/pipeline',   [SalesReportController::class, 'pipeline']   )->name('pipeline');
                Route::get('/conversion', [SalesReportController::class, 'conversion'] )->name('conversion');
                Route::get('/activity',   [SalesReportController::class, 'activity']   )->name('activity');
            });
    });
