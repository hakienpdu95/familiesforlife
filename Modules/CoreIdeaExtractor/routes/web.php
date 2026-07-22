<?php

use Illuminate\Support\Facades\Route;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Http\CoreIdeaExtractorController;

// spec/CoreIdeaExtractor.md — module KHÔNG có Eloquent Model nào nên gate bằng middleware
// 'can:core_idea_extractor.use' trực tiếp (Spatie Permission tự đăng ký permission string này
// làm Gate ability), không cần Policy class (khác Banner/Newsletter — those có Model để check).
Route::middleware(['auth', 'can:core_idea_extractor.use'])
    ->prefix('dashboard/core-idea-extractor')
    ->name('backend.coreideaextractor.')
    ->group(function (): void {
        Route::get('/', [CoreIdeaExtractorController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'can:core_idea_extractor.use'])
    ->prefix('backend/api/core-idea-extractor')
    ->name('backend.api.coreideaextractor.')
    ->group(function (): void {
        Route::post('extract', [CoreIdeaExtractorController::class, 'extract'])->name('extract');
    });
