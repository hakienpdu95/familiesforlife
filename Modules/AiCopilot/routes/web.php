<?php

use Illuminate\Support\Facades\Route;
use Modules\AiCopilot\Http\Controllers\AiAgentController;
use Modules\AiCopilot\Http\Controllers\AiPromptController;
use Modules\AiCopilot\Http\Controllers\AiRequestLogController;
use Modules\AiCopilot\Http\Controllers\AiTaskController;
use Modules\AiCopilot\Http\Controllers\AiUsageController;

Route::middleware(['auth', 'verified', 'feature:module.ai'])
    ->prefix('dashboard/ai')
    ->name('ai.')
    ->group(function () {

        // ── Task execution ────────────────────────────────────────────────
        Route::post('/execute',            [AiTaskController::class, 'execute'])->name('execute');
        Route::get('/requests/{uuid}',     [AiTaskController::class, 'poll'])->name('requests.poll');

        // ── Usage dashboard (ai_copilot.view_usage) ───────────────────────
        Route::get('/usage',               [AiUsageController::class, 'index'])->name('usage.index');

        // ── Agent Config (ai_copilot.config) ─────────────────────────────
        Route::prefix('/agents')->name('agents.')->group(function () {
            Route::get('/',             [AiAgentController::class, 'index'])  ->name('index');
            Route::get('/create',       [AiAgentController::class, 'create']) ->name('create');
            Route::post('/',            [AiAgentController::class, 'store'])  ->name('store');
            Route::get('/{agent}/edit', [AiAgentController::class, 'edit'])   ->name('edit');
            Route::put('/{agent}',      [AiAgentController::class, 'update']) ->name('update');
            Route::delete('/{agent}',   [AiAgentController::class, 'destroy'])->name('destroy');
        });

        // ── Prompt Library (prompt.full) ──────────────────────────────────
        Route::prefix('/prompts')->name('prompts.')->group(function () {
            Route::get('/',                  [AiPromptController::class, 'index'])      ->name('index');
            Route::get('/create',            [AiPromptController::class, 'create'])     ->name('create');
            Route::post('/',                 [AiPromptController::class, 'store'])      ->name('store');
            Route::get('/{prompt}/edit',     [AiPromptController::class, 'edit'])       ->name('edit');
            Route::put('/{prompt}',          [AiPromptController::class, 'update'])     ->name('update');
            Route::post('/{prompt}/default', [AiPromptController::class, 'setDefault'])->name('setDefault');
            Route::delete('/{prompt}',       [AiPromptController::class, 'destroy'])    ->name('destroy');
        });

        // ── Request Logs (ai_logs.full) ───────────────────────────────────
        Route::prefix('/logs')->name('logs.')->group(function () {
            Route::get('/',                      [AiRequestLogController::class, 'index']) ->name('index');
            Route::get('/{aiRequest}',           [AiRequestLogController::class, 'show'])  ->name('show');
            Route::post('/{aiRequest}/retry',    [AiRequestLogController::class, 'retry']) ->name('retry');
        });
    });
