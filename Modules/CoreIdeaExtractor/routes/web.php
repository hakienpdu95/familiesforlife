<?php

use Illuminate\Support\Facades\Route;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Http\CoreIdeaExtractorController;

// spec/CoreIdeaExtractor.md §12 — quyền TRUY CẬP module vẫn gate phẳng bằng middleware
// 'can:core_idea_extractor.use' (Spatie Permission tự đăng ký permission string này làm Gate
// ability). Ngữ cảnh biên tập theo category (Category Content Foundation) đã tách sang module
// dùng chung Modules\ContentFoundation (routes/gate riêng ở đó, xem
// Modules/ContentFoundation/routes/web.php) — module này chỉ còn giữ Layer 1/2 (trích xuất từ URL).
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
        Route::post('extract-batch', [CoreIdeaExtractorController::class, 'extractBatch'])->name('extract-batch');
        Route::post('layer2', [CoreIdeaExtractorController::class, 'runLayer2'])->name('layer2');
        Route::post('summarize', [CoreIdeaExtractorController::class, 'summarize'])->name('summarize');
        Route::post('rewrite', [CoreIdeaExtractorController::class, 'rewrite'])->name('rewrite');
    });
