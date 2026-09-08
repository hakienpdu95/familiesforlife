<?php

use Illuminate\Support\Facades\Route;
use Modules\BulkIdeationPromptStudio\Features\Ideation\Http\IdeationController;

Route::middleware(['auth', 'can:bulk_ideation_prompt_studio.use'])
    ->prefix('dashboard/bulk-ideation-prompt-studio')
    ->name('backend.bulkideationpromptstudio.')
    ->group(function (): void {
        Route::get('/', [IdeationController::class, 'index'])->name('index');
        Route::get('/create', [IdeationController::class, 'create'])->name('create');
        Route::post('/', [IdeationController::class, 'store'])->name('store');
        Route::get('/{prompt:uuid}', [IdeationController::class, 'show'])->name('show');
        Route::delete('/{prompt:uuid}', [IdeationController::class, 'destroy'])->name('destroy');
    });
