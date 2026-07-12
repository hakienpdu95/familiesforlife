<?php

use Illuminate\Support\Facades\Route;
use Modules\Post\Features\ArticleAuthoring\Http\ArticleAdminController;
use Modules\Post\Features\ArticleAuthoring\Http\TranslationController;
use Modules\Post\Features\CategoryManagement\Http\CategoryAdminController;
use Modules\Post\Features\PublicReading\Http\ProductBlockClickController;
use Modules\Post\Features\PublicReading\Http\PublicArticleController;
use Modules\Post\Features\PublicReading\Http\PublicCategoryController;
use Modules\Post\Features\PublicReading\Http\SitemapController;

// ── Quản trị Bài viết + Danh mục (đa ngôn ngữ — Publishing Engine Phase 15,
// xem spec/PublishingEngine_Technical_Specification.md §9) ─────────────────
Route::middleware(['auth', 'tenant'])
    ->prefix('dashboard/posts')
    ->name('backend.post.')
    ->group(function (): void {
        // ->except(['show']): CategoryAdminController không có show() — chưa có yêu cầu
        // trang xem chi tiết 1 danh mục riêng (khác spec §12 gốc, khớp cách Product xử lý)
        Route::resource('categories', CategoryAdminController::class)->except(['show']);
        Route::post('categories/reorder', [CategoryAdminController::class, 'reorder'])->name('categories.reorder');

        // Hàng chờ duyệt xuyên tổ chức (content_editor/content_head) — ĐẶT TRƯỚC
        // Route::resource('articles', ...) bên dưới, vì "articles/{article}" (show) sẽ khớp
        // nhầm "articles/pending-review" nếu resource đăng ký trước (Laravel so khớp theo thứ
        // tự đăng ký, không tự ưu tiên path tường minh hơn wildcard).
        Route::get('articles/pending-review', [ArticleAdminController::class, 'pendingReview'])->name('articles.pending-review');

        // PostArticle: chỉ "vỏ" (format/cover/is_featured/categories/tags) — không còn
        // title/status (đã chuyển sang PostArticleTranslation, xem TranslationController).
        Route::resource('articles', ArticleAdminController::class);
        Route::post('articles/{article}/publish-all', [ArticleAdminController::class, 'publishAll'])->name('articles.publish-all');
        Route::post('articles/{article}/remove-sponsor', [ArticleAdminController::class, 'removeSponsor'])->name('articles.remove-sponsor');

        Route::post('articles/{article}/translations', [TranslationController::class, 'store'])->name('articles.translations.store');
        Route::put('translations/{translation}', [TranslationController::class, 'update'])->name('translations.update');
        Route::delete('translations/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');

        Route::post('translations/{translation}/submit', [TranslationController::class, 'submit'])->name('translations.submit');
        Route::post('translations/{translation}/approve', [TranslationController::class, 'approve'])->name('translations.approve');
        Route::post('translations/{translation}/publish', [TranslationController::class, 'publish'])->name('translations.publish');
        Route::post('translations/{translation}/schedule', [TranslationController::class, 'schedule'])->name('translations.schedule');
        Route::post('translations/{translation}/cancel-schedule', [TranslationController::class, 'cancelSchedule'])->name('translations.cancel-schedule');
        Route::post('translations/{translation}/unpublish', [TranslationController::class, 'unpublish'])->name('translations.unpublish');
        Route::post('translations/{translation}/takedown', [TranslationController::class, 'takedown'])->name('translations.takedown');
        Route::post('translations/{translation}/archive', [TranslationController::class, 'archive'])->name('translations.archive');
    });

// ── Click tracking CTA (public — không yêu cầu đăng nhập) ──────────────────
Route::get('posts/cta/{button}', [ProductBlockClickController::class, 'redirect'])->name('post.cta.redirect');

// ── PublicReading — Phase 16 (spec/PublishingEngine_Technical_Specification.md §11) ────
// 'tenant' (không có 'auth') — IdentifyOrganization resolve tổ chức qua subdomain cho
// khách vãng lai; nếu không resolve được, OrganizationScope tự trả rỗng (an toàn, không
// leak chéo tổ chức) thay vì 500. Không ràng buộc {locale} bằng regex ở route (đọc
// config('post.locales') tại thời điểm nạp route file không đáng tin cậy — có thể chạy
// trước khi module config merge xong, tuỳ context boot) — mỗi controller tự
// abort_unless(array_key_exists($locale, config('post.locales')), 404) khi xử lý request.
Route::middleware(['tenant'])->group(function (): void {
    Route::get('post-sitemap.xml', [SitemapController::class, 'index'])->name('post.public.sitemap');

    Route::prefix('{locale}/bai-viet')->name('post.public.')->group(function (): void {
        Route::get('/', [PublicCategoryController::class, 'index'])->name('home');
        Route::get('danh-muc/{category:slug}', [PublicCategoryController::class, 'show'])->name('category');
        Route::get('{slug}', [PublicArticleController::class, 'show'])->name('article');
    });
});
