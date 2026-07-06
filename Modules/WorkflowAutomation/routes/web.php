<?php

use App\Enums\PermissionEnum as P;
use Illuminate\Support\Facades\Route;
use Modules\WorkflowAutomation\Http\Controllers\WorkflowApiController;
use Modules\WorkflowAutomation\Http\Controllers\WorkflowController;
use Modules\WorkflowAutomation\Http\Controllers\WorkflowExecutionController;
use Modules\WorkflowAutomation\Http\Controllers\WorkflowUserTaskController;

// Web routes — admin UI
Route::prefix('dashboard/workflows')
    ->middleware(['web', 'auth', 'can:' . P::WORKFLOW_MONITOR->value, 'feature:module.workflow'])
    ->name('workflows.')
    ->group(function () {
        Route::get('/',                        [WorkflowController::class, 'index'])    ->name('index');
        Route::get('/create',                  [WorkflowController::class, 'create'])   ->name('create')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::post('/',                       [WorkflowController::class, 'store'])    ->name('store')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::get('/{workflow}',              [WorkflowController::class, 'show'])     ->name('show');
        Route::get('/{workflow}/edit',         [WorkflowController::class, 'edit'])     ->name('edit')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::put('/{workflow}',              [WorkflowController::class, 'update'])   ->name('update')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::delete('/{workflow}',           [WorkflowController::class, 'destroy'])  ->name('destroy')
            ->middleware('can:' . P::WORKFLOW_FULL_CONFIG->value);
        Route::patch('/{workflow}/toggle',     [WorkflowController::class, 'toggle'])   ->name('toggle')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::post('/{workflow}/run',         [WorkflowController::class, 'manualRun'])->name('run')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::get('/{workflow}/executions',   [WorkflowExecutionController::class, 'index'])->name('executions');
        Route::get('/executions/{execution}',  [WorkflowExecutionController::class, 'show']) ->name('executions.show');
    });

// Human-in-the-Loop user task routes (§9.2 — Mô hình C)
Route::prefix('workflow/tasks')
    ->middleware(['web', 'auth'])
    ->name('workflow.tasks.')
    ->group(function () {
        Route::get('/my',                           [WorkflowUserTaskController::class, 'myTasks']) ->name('my');
        Route::get('/{token}',                      [WorkflowUserTaskController::class, 'show'])    ->name('show');
        Route::post('/{token}/respond',             [WorkflowUserTaskController::class, 'respond']) ->name('respond');
    });

// API routes — Tabulator + Builder
Route::prefix('backend/api/workflows')
    ->middleware(['web', 'auth', 'can:' . P::WORKFLOW_MONITOR->value, 'feature:module.workflow'])
    ->name('backend.api.workflows.')
    ->group(function () {
        Route::get('/',                        [WorkflowApiController::class, 'index'])        ->name('index');
        Route::get('/meta',                    [WorkflowApiController::class, 'meta'])         ->name('meta');
        Route::get('/executions',              [WorkflowApiController::class, 'executions'])   ->name('executions');
        Route::get('/stats',                   [WorkflowApiController::class, 'stats'])        ->name('stats');
        Route::get('/subject-fields/{type}',   [WorkflowApiController::class, 'subjectFields'])->name('subject-fields');
    });
