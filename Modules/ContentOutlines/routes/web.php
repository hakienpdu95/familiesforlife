<?php

use Illuminate\Support\Facades\Route;
use Modules\ContentOutlines\Features\OutlineGeneration\Http\ContentOutlineApiController;
use Modules\ContentOutlines\Features\OutlineGeneration\Http\ContentOutlineController;

// spec/ContentOutlines_Technical_Specification.md §7.2 — gate phẳng bằng permission
// 'content_outlines.use' (cùng nhóm CORE_IDEA_EXTRACTOR_USE/CONTENT_FOUNDATION_USE), KHÔNG
// Policy riêng theo model (§2.1 — không owner-based ACL).
Route::middleware(['auth', 'can:content_outlines.use'])
    ->prefix('dashboard/content-outlines')
    ->name('backend.contentoutlines.')
    ->group(function (): void {
        Route::get('/', [ContentOutlineController::class, 'index'])->name('index');
        Route::get('create', [ContentOutlineController::class, 'create'])->name('create');
        Route::post('/', [ContentOutlineController::class, 'store'])->name('store');
        Route::get('{outline}', [ContentOutlineController::class, 'show'])->name('show');
        Route::get('{outline}/edit', [ContentOutlineController::class, 'edit'])->name('edit');
        Route::put('{outline}', [ContentOutlineController::class, 'update'])->name('update'); // = "Sinh lại"
        Route::delete('{outline}', [ContentOutlineController::class, 'destroy'])->name('destroy');
        Route::post('{outline}/link-article', [ContentOutlineController::class, 'linkArticle'])->name('link-article');
    });

// JSON backend cho Tabulator (session-based auth, cùng guard trang quản trị).
Route::middleware(['auth', 'can:content_outlines.use'])
    ->prefix('backend/api/content-outlines')
    ->name('backend.api.contentoutlines.')
    ->group(function (): void {
        Route::get('items', [ContentOutlineApiController::class, 'index'])->name('items');
    });
