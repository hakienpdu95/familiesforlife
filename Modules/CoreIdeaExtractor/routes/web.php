<?php

use Illuminate\Support\Facades\Route;
use Modules\CoreIdeaExtractor\Features\CategoryFoundation\Http\CategoryFoundationController;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Http\CoreIdeaExtractorController;

// spec/CoreIdeaExtractor.md §12 (v1.4) — quyền TRUY CẬP module vẫn gate phẳng bằng middleware
// 'can:core_idea_extractor.use' (Spatie Permission tự đăng ký permission string này làm Gate
// ability). Quyền SỬA foundation của 1 category cụ thể xem thêm Gate::authorize() trong
// CategoryFoundationController::upsert() (ability 'core_idea_extractor.manage_category_foundation',
// định nghĩa ở CoreIdeaExtractorServiceProvider::boot()).
Route::middleware(['auth', 'can:core_idea_extractor.use'])
    ->prefix('dashboard/core-idea-extractor')
    ->name('backend.coreideaextractor.')
    ->group(function (): void {
        Route::get('/', [CoreIdeaExtractorController::class, 'index'])->name('index');
        Route::get('/category-foundations', [CategoryFoundationController::class, 'index'])->name('category-foundations.index');
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
        Route::get('category-foundations', [CategoryFoundationController::class, 'list'])->name('category-foundations.list');
        Route::put('category-foundations/{category}', [CategoryFoundationController::class, 'upsert'])->name('category-foundations.upsert');
        Route::get('category-foundations/{category}/existing-articles', [CategoryFoundationController::class, 'existingArticles'])->name('category-foundations.existing-articles');
    });
