<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\ReportDashboardController;
use Modules\Report\Http\Controllers\HrReportController;
use Modules\Report\Http\Controllers\SalesReportController;

Route::middleware(['auth', 'verified', 'tenant'])
    ->prefix('report')
    ->name('report.')
    ->group(function () {

        Route::get('/', [ReportDashboardController::class, 'index'])->name('index');

        // ── HR ──────────────────────────────────────────────────────
        Route::middleware('can:reports.hr,reports.full')
            ->prefix('hr')->name('hr.')
            ->group(function () {
                Route::get('/',            [HrReportController::class, 'index']      )->name('index');
                Route::get('/headcount',   [HrReportController::class, 'headcount']  )->name('headcount');
            });

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
