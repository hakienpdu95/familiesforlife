<?php

use Illuminate\Support\Facades\Route;
use Modules\Post\Features\ArticleAuthoring\Http\ArticleAdminController;
use Modules\Post\Features\CategoryManagement\Http\CategoryAdminController;
use Modules\Post\Features\PublicReading\Http\ProductBlockClickController;

// ── Quản trị Bài viết + Danh mục — Phase 0-4 (chưa có Product CTA Box, xem
// docs/post-module-spec.md §17 Phase 5+) ───────────────────────────────────
Route::middleware(['auth', 'tenant'])
    ->prefix('dashboard/posts')
    ->name('backend.post.')
    ->group(function (): void {
        // ->except(['show']): CategoryAdminController không có show() — chưa có yêu cầu
        // trang xem chi tiết 1 danh mục riêng (khác spec §12 gốc, khớp cách Product xử lý)
        Route::resource('categories', CategoryAdminController::class)->except(['show']);
        Route::post('categories/reorder', [CategoryAdminController::class, 'reorder'])->name('categories.reorder');

        Route::resource('articles', ArticleAdminController::class);
        Route::post('articles/{article}/submit', [ArticleAdminController::class, 'submit'])->name('articles.submit');
        Route::post('articles/{article}/publish', [ArticleAdminController::class, 'publish'])->name('articles.publish');
        Route::post('articles/{article}/schedule', [ArticleAdminController::class, 'schedule'])->name('articles.schedule');
        Route::post('articles/{article}/archive', [ArticleAdminController::class, 'archive'])->name('articles.archive');
    });

// ── Click tracking CTA (public — không yêu cầu đăng nhập) ──────────────────
// Trang danh sách/chi tiết bài viết công khai (PublicCategoryController/PublicArticleController)
// chưa triển khai — xem docs/post-module-spec.md §17 Phase 9-10.
Route::get('posts/cta/{button}', [ProductBlockClickController::class, 'redirect'])->name('post.cta.redirect');
