<?php

use Illuminate\Support\Facades\Route;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Http\VideoIdeaExtractorController;

Route::middleware(['auth', 'can:video_idea_extractor.use'])
    ->prefix('dashboard/video-idea-extractor')
    ->name('backend.videoideaextractor.')
    ->group(function (): void {
        Route::get('/', [VideoIdeaExtractorController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'can:video_idea_extractor.use'])
    ->prefix('backend/api/video-idea-extractor')
    ->name('backend.api.videoideaextractor.')
    ->group(function (): void {
        Route::post('extract-batch', [VideoIdeaExtractorController::class, 'extractBatch'])->name('extract-batch');
        Route::post('layer2', [VideoIdeaExtractorController::class, 'runLayer2'])->name('layer2');
        Route::post('titles', [VideoIdeaExtractorController::class, 'titles'])->name('titles');
        Route::post('hooks', [VideoIdeaExtractorController::class, 'hooks'])->name('hooks');
        Route::post('shorts', [VideoIdeaExtractorController::class, 'shorts'])->name('shorts');
        Route::post('outline', [VideoIdeaExtractorController::class, 'outline'])->name('outline');
        Route::post('cta', [VideoIdeaExtractorController::class, 'cta'])->name('cta');
        Route::post('polish', [VideoIdeaExtractorController::class, 'polish'])->name('polish');
        Route::post('vlog-outline', [VideoIdeaExtractorController::class, 'vlogOutline'])->name('vlog-outline');
    });
