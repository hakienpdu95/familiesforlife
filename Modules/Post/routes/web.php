<?php

use Illuminate\Support\Facades\Route;
use Modules\Post\Features\ArticleAuthoring\Http\ArticleAdminController;
use Modules\Post\Features\ArticleAuthoring\Http\ArticleApiController;
use Modules\Post\Features\ArticleAuthoring\Http\TranslationController;
use Modules\Post\Features\BreakingNews\Http\BreakingNewsAdminController;
use Modules\Post\Features\BreakingNews\Http\BreakingNewsApiController;
use Modules\Post\Features\BreakingNews\Http\BreakingNewsPublicController;
use Modules\Post\Features\CategoryManagement\Http\CategoryAdminController;
use Modules\Post\Features\CategoryManagement\Http\CategoryApiController;
use Modules\Post\Features\PublicReading\Http\ProductBlockClickController;
use Modules\Post\Features\PublicReading\Http\PublicArticleController;
use Modules\Post\Features\PublicReading\Http\PublicCategoryController;
use Modules\Post\Features\PublicReading\Http\SitemapController;
use Modules\Post\Features\TagManagement\Http\TagAdminController;
use Modules\Post\Features\TagManagement\Http\TagApiController;
use Modules\Post\Features\VersionHistory\Http\ArticleVersionController;

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

        // spec/PostTag_Management_Technical_Specification.md §3.1/§3.2 — TagAdminController
        // không có show() (chưa có yêu cầu trang xem chi tiết 1 tag riêng, cùng lý do
        // 'categories' ở trên); 'merge' là action riêng ngoài 7 method chuẩn REST.
        Route::resource('tags', TagAdminController::class)->except(['show']);
        Route::post('tags/{tag}/merge', [TagAdminController::class, 'merge'])->name('tags.merge');

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
        // format=redirect — thống kê lượt click (xem GetArticleRedirectClickStatsHandler).
        Route::get('articles/{article}/clicks', [ArticleAdminController::class, 'clicks'])->name('articles.clicks');

        Route::post('articles/{article}/translations', [TranslationController::class, 'store'])->name('articles.translations.store');
        Route::put('translations/{translation}', [TranslationController::class, 'update'])->name('translations.update');
        Route::delete('translations/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');

        // spec/Post_VersionHistory_Technical_Specification.md §13.2 — 'versions/compare' PHẢI
        // đăng ký TRƯỚC 'versions/{version}' (show): cùng lý do 'articles/pending-review' đặt
        // trước Route::resource('articles', ...) ở trên — nếu không, "compare" sẽ bị model-bind
        // nhầm thành {version} (ném ModelNotFoundException) trước khi khớp được route compare.
        Route::get('translations/{translation}/versions', [ArticleVersionController::class, 'index'])->name('translations.versions.index');
        Route::get('translations/{translation}/versions/compare', [ArticleVersionController::class, 'compare'])->name('translations.versions.compare');
        Route::get('translations/{translation}/versions/{version}', [ArticleVersionController::class, 'show'])->name('translations.versions.show');
        Route::post('translations/{translation}/versions/{version}/restore', [ArticleVersionController::class, 'restore'])->name('translations.versions.restore');

        Route::post('translations/{translation}/submit', [TranslationController::class, 'submit'])->name('translations.submit');
        Route::post('translations/{translation}/approve', [TranslationController::class, 'approve'])->name('translations.approve');
        Route::post('translations/{translation}/publish', [TranslationController::class, 'publish'])->name('translations.publish');
        Route::post('translations/{translation}/schedule', [TranslationController::class, 'schedule'])->name('translations.schedule');
        Route::post('translations/{translation}/cancel-schedule', [TranslationController::class, 'cancelSchedule'])->name('translations.cancel-schedule');
        Route::post('translations/{translation}/unpublish', [TranslationController::class, 'unpublish'])->name('translations.unpublish');
        Route::post('translations/{translation}/takedown', [TranslationController::class, 'takedown'])->name('translations.takedown');
        Route::post('translations/{translation}/archive', [TranslationController::class, 'archive'])->name('translations.archive');
    });

// ── Backend JSON API cho Tabulator (session-based auth, cùng guard trang quản trị) —
// tham chiếu Modules/Organization/routes/web.php (backend.api.organizations) ───────────────
Route::middleware(['auth'])->prefix('backend/api/post')->name('backend.api.post.')->group(function (): void {
    Route::get('articles', [ArticleApiController::class, 'index'])->name('articles');
    Route::get('categories', [CategoryApiController::class, 'index'])->name('categories');
    Route::get('tags', [TagApiController::class, 'index'])->name('tags');
});

// ── Breaking News — spec/Breaking_News_Ticker_Technical_Specification.md §6.1 ───────────────
Route::middleware(['auth'])->prefix('dashboard/breaking-news')->name('backend.post.breaking-news.')
    ->group(function (): void {
        Route::resource('items', BreakingNewsAdminController::class)->except(['show'])
            ->parameters(['items' => 'breakingNews']);
    });

Route::middleware(['auth'])->prefix('backend/api/breaking-news')->name('backend.api.breaking-news.')
    ->group(function (): void {
        Route::get('items', [BreakingNewsApiController::class, 'index'])->name('items');
        // §6.2 — autocomplete "chọn bài viết" (TomSelect remote), gated breaking_news.manage
        // (KHÔNG dùng lại backend.api.post.articles — xem docblock searchArticles()).
        Route::get('articles/search', [BreakingNewsApiController::class, 'searchArticles'])->name('articles.search');
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

// spec/Breaking_News_Ticker_Technical_Specification.md §7.4 — polling JSON công khai, ticker
// tự gọi định kỳ (config('post.breaking_news.poll_seconds')) để cập nhật không cần F5.
Route::get('tin-nong/hien-tai', [BreakingNewsPublicController::class, 'current'])->name('post.public.breaking-news.current');

Route::get('/', [PublicCategoryController::class, 'index'])->name('post.public.home');

// Bỏ hẳn prefix 'bai-viet' (trước: /bai-viet/danh-muc/{slug}, /bai-viet/{slug}) — URL gọn hơn,
// theo đúng convention báo VN (vd treemvietnam.net.vn: /{category-slug}, /{slug}-d{id}.html).
// Route::name(...) KHÔNG kèm URI prefix — tên route (post.public.category/load-more/article)
// giữ nguyên, chỉ đổi URI, nên toàn bộ route()/href hiện có ở view khác không cần sửa TÊN,
// chỉ những nơi build URL bài viết cần thêm tham số 'id' (xem bên dưới).
Route::name('post.public.')->group(function (): void {
    // 'danh-muc/{slug}' vẫn giữ 1 segment tiền tố riêng (không đưa category thẳng ra root như
    // ví dụ treemvietnam) — tránh phải né toàn bộ route root-level của CẢ APP (login, dashboard,
    // su-kien, ocop, ...) mỗi khi có route mới; rủi ro trùng slug chỉ còn khoanh trong
    // 'danh-muc/*', không lan sang toàn hệ thống.
    Route::get('danh-muc/{category:slug}', [PublicCategoryController::class, 'show'])->name('category');

    // 'tai-them' (Xem thêm — trang chủ) không có tham số wildcard nên đặt ở root không rủi ro
    // đụng độ (không khớp mẫu 'danh-muc/*' cũng không khớp '{slug}-d{id}.html' — mẫu bài viết
    // bên dưới LUÔN đòi hỏi đuôi '.html' + id số nên không bao giờ trùng path tĩnh này).
    Route::get('tai-them', [PublicCategoryController::class, 'loadMore'])->name('load-more');

    // '{slug}-d{id}.html' — hậu tố '-d{id}.html' (id số, KHÔNG dùng để tra cứu, chỉ để phân
    // biệt path với 'danh-muc/*'/'tai-them' ở root) là cơ chế tách bạch tuyệt đối bằng regex,
    // không phụ thuộc thứ tự đăng ký route như '{slug}' wildcard trần trước đây. Controller vẫn
    // tra theo 'slug' như cũ (Rule::unique('post_article_translations','slug') đã đảm bảo duy
    // nhất toàn hệ thống) — {id} không khai báo trong tham số PublicArticleController::show(),
    // Laravel tự bỏ qua route-param không có trong signature, không cần đọc.
    Route::get('{slug}-d{id}.html', [PublicArticleController::class, 'show'])
        ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])
        ->name('article');
});
