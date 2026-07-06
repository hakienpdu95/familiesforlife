<?php

use Illuminate\Support\Facades\Route;
use Modules\Task\Http\Controllers\Api\MyTasksApiController;
use Modules\Task\Http\Controllers\Api\ProjectAnalyticsApiController;
use Modules\Task\Http\Controllers\Api\TimeLogApiController;

/*
|--------------------------------------------------------------------------
| Task Module — API v1 Routes (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->name('task.')->group(function () {

    // My tasks — cross-project dashboard
    Route::get('tasks/my', [MyTasksApiController::class, 'index'])->name('my');

    // Time logs per task
    Route::get('tasks/{task}/time-logs', [TimeLogApiController::class, 'index'])->name('time-logs.index');
    Route::post('tasks/{task}/time-logs', [TimeLogApiController::class, 'store'])->name('time-logs.store');

    // Time log update / delete (resource-level, no task nesting)
    Route::put('time-logs/{log}', [TimeLogApiController::class, 'update'])->name('time-logs.update');
    Route::delete('time-logs/{log}', [TimeLogApiController::class, 'destroy'])->name('time-logs.destroy');

    // Project analytics
    Route::get('projects/{project}/time-report', [ProjectAnalyticsApiController::class, 'timeReport'])->name('projects.time-report');
    Route::get('projects/{project}/progress', [ProjectAnalyticsApiController::class, 'progress'])->name('projects.progress');
    Route::get('projects/{project}/stats', [ProjectAnalyticsApiController::class, 'stats'])->name('projects.stats');
});
