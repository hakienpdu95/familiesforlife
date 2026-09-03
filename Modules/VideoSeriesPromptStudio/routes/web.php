<?php

use Illuminate\Support\Facades\Route;
use Modules\VideoSeriesPromptStudio\Features\SeriesArchitecture\Http\SeriesArchitectureController;

Route::middleware(['auth', 'can:video_series_prompt_studio.use'])
    ->prefix('dashboard/video-series-prompt-studio')
    ->name('backend.videoseriespromptstudio.')
    ->group(function (): void {
        Route::get('/', [SeriesArchitectureController::class, 'index'])->name('index');
        Route::get('/create', [SeriesArchitectureController::class, 'create'])->name('create');
        Route::post('/', [SeriesArchitectureController::class, 'store'])->name('store');
        Route::get('/{prompt:uuid}', [SeriesArchitectureController::class, 'show'])->name('show');
        Route::delete('/{prompt:uuid}', [SeriesArchitectureController::class, 'destroy'])->name('destroy');
    });
