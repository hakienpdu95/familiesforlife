<?php

use Illuminate\Support\Facades\Route;
use Modules\KpiGoal\Http\Controllers\KpiGoalController;

/*
|--------------------------------------------------------------------------
| KpiGoal Module — Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {

    // ── KPI Goals CRUD ──────────────────────────────────────────────────────────
    Route::prefix('kpi/goals')->name('kpi.goals.')->group(function () {
        Route::get('/',              [KpiGoalController::class, 'index'])->name('index');
        Route::get('/create',        [KpiGoalController::class, 'create'])->name('create');
        Route::post('/',             [KpiGoalController::class, 'store'])->name('store');
        Route::get('/{goal}',        [KpiGoalController::class, 'show'])->name('show');
        Route::get('/{goal}/edit',   [KpiGoalController::class, 'edit'])->name('edit');
        Route::put('/{goal}',        [KpiGoalController::class, 'update'])->name('update');
        Route::post('/{goal}/approve',         [KpiGoalController::class, 'approve'])->name('approve');
        Route::post('/{goal}/progress',        [KpiGoalController::class, 'updateProgress'])->name('progress');
    });

    // ── Cycle close ─────────────────────────────────────────────────────────────
    Route::post('kpi/cycles/close', [KpiGoalController::class, 'closeCycle'])->name('kpi.cycles.close');

    // ── Leaderboard ─────────────────────────────────────────────────────────────
    Route::get('kpi/leaderboard',   [KpiGoalController::class, 'leaderboard'])->name('kpi.leaderboard');

    // ── KPI internal API ────────────────────────────────────────────────────────
    Route::get('kpi/api/employees', [KpiGoalController::class, 'apiEmployees'])->name('kpi.api.employees');

});
