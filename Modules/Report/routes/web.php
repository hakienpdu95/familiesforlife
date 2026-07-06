<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\CompetencyReportController;
use Modules\Report\Http\Controllers\ReportDashboardController;
use Modules\Report\Http\Controllers\HrReportController;
use Modules\Report\Http\Controllers\SalesReportController;
use Modules\Report\Http\Controllers\ProjectKpiReportController;

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
                Route::get('/leave',       [HrReportController::class, 'leave']      )->name('leave');
                Route::get('/recruitment', [HrReportController::class, 'recruitment'])->name('recruitment');
                Route::get('/performance', [HrReportController::class, 'performance'])->name('performance');
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

        // ── Project ──────────────────────────────────────────────────
        Route::middleware('can:reports.ops,reports.full')
            ->prefix('project')->name('project.')
            ->group(function () {
                Route::get('/',      [ProjectKpiReportController::class, 'projectIndex'])->name('index');
                Route::get('/tasks', [ProjectKpiReportController::class, 'tasks']       )->name('tasks');
            });

        // ── KPI ──────────────────────────────────────────────────────
        Route::middleware('can:reports.ops,reports.full')
            ->prefix('kpi')->name('kpi.')
            ->group(function () {
                Route::get('/',         [ProjectKpiReportController::class, 'kpiIndex']   )->name('index');
                Route::get('/cycle',    [ProjectKpiReportController::class, 'kpiCycle']   )->name('cycle');
                Route::get('/snapshot', [ProjectKpiReportController::class, 'kpiSnapshot'])->name('snapshot');
            });

        // ── Competency ───────────────────────────────────────────────
        Route::middleware('can:reports.hr,reports.full')
            ->prefix('competency')->name('competency.')
            ->group(function () {
                Route::get('/',          [CompetencyReportController::class, 'index']    )->name('index');
                Route::get('/heatmap',   [CompetencyReportController::class, 'heatmap'] )->name('heatmap');
                Route::get('/skill-gap', [CompetencyReportController::class, 'skillGap'])->name('skill-gap');
                Route::get('/trends',    [CompetencyReportController::class, 'trends']  )->name('trends');
                Route::get('/export',    [CompetencyReportController::class, 'export']  )->name('export');
            });
    });
