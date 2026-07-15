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
// KHÔNG còn 'tenant' middleware (spec/Platform_RBAC_Phase2_Specification.md §3.5/§4, v3.0) —
// Post (bài viết + category) không còn organization_id nào cần TenantContext để scope; nhân
// sự nền tảng (organization_id=null) thao tác mọi route ở đây, đúng pattern đã dùng cho
// `dashboard/platform-users` (chỉ 'auth', không 'tenant' — xem PlatformUserController).
Route::middleware(['auth'])
    ->prefix('dashboard/posts')
    ->name('backend.post.')
    ->group(function (): void {
        // ->except(['show']): CategoryAdminController không có show() — chưa có yêu cầu
        // trang xem chi tiết 1 danh mục riêng (khác spec §12 gốc, khớp cách Product xử lý)
        Route::resource('categories', CategoryAdminController::class)->except(['show']);
        Route::post('categories/reorder', [CategoryAdminController::class, 'reorder'])->name('categories.reorder');

        // Hàng chờ duyệt xuyên tổ chức (platform_content_editor/platform_content_head) — ĐẶT TRƯỚC
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
// KHÔNG còn middleware 'tenant' (spec/Platform_RBAC_Phase2_Specification.md §3.5, v3.0) —
// Post không còn organization_id (§3.3), nên resolve tổ chức theo subdomain không còn ý
// nghĩa gì cho các route này: bài viết phục vụ đồng nhất cho mọi domain/subdomain, không cần
// biết đang "đứng" ở tổ chức nào trước khi trả nội dung. Bỏ 'auth' vẫn giữ (route công khai,
// không yêu cầu đăng nhập).
//
// KHÔNG còn {locale} trong URL (trước là /{locale}/bai-viet, vd /en/bai-viet) — toàn bộ nội
// dung thực tế chỉ có tiếng Việt nên phần locale trên URL chỉ gây rối, không phục vụ mục đích
// gì; PublicCategoryController/PublicArticleController tự dùng config('post.default_locale')
// nội bộ. Trang chủ đăng ký thẳng tại domain gốc ('/'), không phải '/bai-viet' — tránh 2 URL
// cùng phục vụ 1 nội dung (trùng lặp SEO).
Route::get('post-sitemap.xml', [SitemapController::class, 'index'])->name('post.public.sitemap');

Route::get('/', [PublicCategoryController::class, 'index'])->name('post.public.home');

Route::prefix('bai-viet')->name('post.public.')->group(function (): void {
    Route::get('danh-muc/{category:slug}', [PublicCategoryController::class, 'show'])->name('category');

    // 'tai-them' (Xem thêm — trang chủ) là path tường minh, phải đăng ký TRƯỚC '{slug}'
    // (wildcard) — cùng lý do 'danh-muc' ở trên.
    Route::get('tai-them', [PublicCategoryController::class, 'loadMore'])->name('load-more');

    Route::get('{slug}', [PublicArticleController::class, 'show'])->name('article');
});
