<?php

use Illuminate\Support\Facades\Route;
use Modules\ContentFoundation\Http\Controllers\CategoryFoundationController;

// spec/CoreIdeaExtractor.md §12 — trang quản lý ngữ cảnh biên tập theo category, dùng CHUNG bởi
// mọi module nghiên cứu ý tưởng nội dung (CoreIdeaExtractor, VideoIdeaExtractor...), không nhân
// bản theo từng module tiêu thụ.
Route::middleware(['auth', 'can:content_foundation.use'])
    ->prefix('dashboard/content-foundation')
    ->name('backend.contentfoundation.')
    ->group(function (): void {
        Route::get('/', [CategoryFoundationController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'can:content_foundation.use'])
    ->prefix('backend/api/content-foundation')
    ->name('backend.api.contentfoundation.')
    ->group(function (): void {
        Route::get('category-foundations', [CategoryFoundationController::class, 'list'])->name('category-foundations.list');
        Route::get('category-foundations/{category}', [CategoryFoundationController::class, 'show'])->name('category-foundations.show');
        Route::put('category-foundations/{category}', [CategoryFoundationController::class, 'upsert'])->name('category-foundations.upsert');
        Route::get('category-foundations/{category}/existing-articles', [CategoryFoundationController::class, 'existingArticles'])->name('category-foundations.existing-articles');
    });
