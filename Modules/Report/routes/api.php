<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\Api\HrReportApiController;
use Modules\Report\Http\Controllers\Api\SalesReportApiController;
use Modules\Report\Http\Controllers\Api\ProjectKpiApiController;

Route::middleware(['auth:sanctum', 'tenant'])
    ->prefix('v1/report')
    ->name('api.report.')
    ->group(function () {

        // ── HR ──────────────────────────────────────────────────────
        Route::middleware('can:reports.hr,reports.full')->group(function () {
            Route::get('/hr/headcount',   [HrReportApiController::class, 'headcount']  )->name('hr.headcount');
            Route::get('/hr/leave',       [HrReportApiController::class, 'leave']      )->name('hr.leave');
            Route::get('/hr/recruitment', [HrReportApiController::class, 'recruitment'])->name('hr.recruitment');
            Route::get('/hr/performance', [HrReportApiController::class, 'performance'])->name('hr.performance');
        });

        // ── Sales ────────────────────────────────────────────────────
        Route::middleware('can:reports.team,reports.personal,reports.full')->group(function () {
            Route::get('/sales/pipeline',   [SalesReportApiController::class, 'pipeline']  )->name('sales.pipeline');
            Route::get('/sales/conversion', [SalesReportApiController::class, 'conversion'])->name('sales.conversion');
            Route::get('/sales/activity',   [SalesReportApiController::class, 'activity']  )->name('sales.activity');
        });

        // ── Project & KPI ────────────────────────────────────────────
        Route::middleware('can:reports.ops,reports.full')->group(function () {
            Route::get('/project/overview', [ProjectKpiApiController::class, 'overview']    )->name('project.overview');
            Route::get('/project/tasks',    [ProjectKpiApiController::class, 'tasks']       )->name('project.tasks');
            Route::get('/kpi/cycle',        [ProjectKpiApiController::class, 'kpiCycle']    )->name('kpi.cycle');
            Route::get('/kpi/snapshot',     [ProjectKpiApiController::class, 'kpiSnapshot'] )->name('kpi.snapshot');
        });
    });
