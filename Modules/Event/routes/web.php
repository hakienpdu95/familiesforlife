<?php

use Illuminate\Support\Facades\Route;
use Modules\Event\Features\EventCategoryManagement\Http\EventCategoryAdminController;
use Modules\Event\Features\EventCategoryManagement\Http\EventCategoryApiController;
use Modules\Event\Features\EventModeration\Http\EventAdminController;
use Modules\Event\Features\EventModeration\Http\EventApiController;
use Modules\Event\Features\PublicReading\Http\EventSitemapController;
use Modules\Event\Features\PublicReading\Http\PublicEventController;
use Modules\Event\Features\PublicSubmission\Http\EventSubmissionController;
use Modules\Event\Features\PublicSubmission\Http\Middleware\ValidateEventTurnstile;

// ── Quản trị Sự kiện (spec/Event_Management_Technical_Specification.md §13) ────────────────
// KHÔNG có 'tenant' middleware — Event là tài sản nền tảng (không organization_id), cùng
// pattern Post (xem §3.2). create/store/edit/update phục vụ staff tự nhập sự kiện trực tiếp
// (EventPolicy::create()/update() — editor/head/ops). approve/reject/publish/archive dùng
// role-helper trong EventPolicy (editor/head), không qua permission string.
Route::middleware(['auth'])
    ->prefix('dashboard/events')
    ->name('backend.event.')
    ->group(function (): void {
        Route::resource('categories', EventCategoryAdminController::class)->except(['show']);
        Route::post('categories/reorder', [EventCategoryAdminController::class, 'reorder'])->name('categories.reorder');

        Route::get('/', [EventAdminController::class, 'index'])->name('index');
        Route::get('create', [EventAdminController::class, 'create'])->name('create');
        Route::post('/', [EventAdminController::class, 'store'])->name('store');
        Route::get('{event}/edit', [EventAdminController::class, 'edit'])->name('edit');
        Route::put('{event}', [EventAdminController::class, 'update'])->name('update');
        Route::post('{event}/approve', [EventAdminController::class, 'approve'])->name('approve');
        Route::post('{event}/reject', [EventAdminController::class, 'reject'])->name('reject');
        Route::post('{event}/publish', [EventAdminController::class, 'publish'])->name('publish');
        Route::post('{event}/archive', [EventAdminController::class, 'archive'])->name('archive');
    });

// ── Backend JSON API cho Tabulator (session-based auth, cùng guard trang quản trị) —
// tham chiếu Modules/Organization/routes/web.php (backend.api.organizations) ───────────────
Route::middleware(['auth'])->prefix('backend/api/events')->name('backend.api.events.')->group(function () {
    Route::get('items', [EventApiController::class, 'index'])->name('items');
    Route::get('categories', [EventCategoryApiController::class, 'index'])->name('categories');
});

// ── Cổng thông tin công khai (spec §8/§13 Phase 3) — không {locale}, cùng quyết định đã áp
// dụng cho Post. THỨ TỰ quan trọng: 'gui-su-kien' (nộp sự kiện) và 'danh-muc/{category:slug}'
// là path tường minh, phải đăng ký TRƯỚC 'su-kien/{slug}' (wildcard) — Laravel so khớp route
// theo đúng thứ tự đăng ký, không tự ưu tiên path cụ thể hơn wildcard (cùng lý do đã áp dụng ở
// Post routes/web.php: articles/pending-review trước articles/{article}).
Route::get('event-sitemap.xml', [EventSitemapController::class, 'index'])->name('event.public.sitemap');

// URL rút gọn cùng kiểu đã áp dụng cho Post (routes/web.php module Post) — RIÊNG danh mục sự
// kiện KHÔNG bỏ được prefix 'su-kien' như Post đã làm với 'danh-muc': Post đã chiếm mất
// 'danh-muc/{slug}' ở root rồi, nếu Event cũng đăng ký y nguyên URI đó ở root thì 2 route trùng
// hệt nhau — route đăng ký sau sẽ không bao giờ được gọi tới (danh mục sự kiện "biến mất" âm
// thầm). Giữ 'su-kien/danh-muc/{slug}' là cách duy nhất tránh đụng độ mà không phải đổi cấu
// trúc Post đã làm trước.
Route::prefix('su-kien')->group(function (): void {
    Route::get('/', [PublicEventController::class, 'index'])->name('event.public.home');
    Route::get('danh-muc/{category:slug}', [PublicEventController::class, 'category'])->name('event.public.category');

    // "Xem thêm sự kiện" (load-more, Alpine) — cùng mẫu 'tai-them' của Post
    // (Modules/Post/routes/web.php, route post.public.load-more).
    Route::get('tai-them', [PublicEventController::class, 'loadMore'])->name('event.public.load-more');

    Route::prefix('gui-su-kien')->name('event.public.submit.')->group(function (): void {
        Route::get('/', [EventSubmissionController::class, 'create'])->name('form');
        Route::post('/', [EventSubmissionController::class, 'store'])
            ->middleware(['throttle:event-submit', ValidateEventTurnstile::class])
            ->name('store');
        Route::get('thanh-cong', [EventSubmissionController::class, 'success'])->name('success');
    });
});

// Chi tiết sự kiện: bỏ prefix 'su-kien', ra root '{slug}-sk{id}.html' — cùng cơ chế hậu tố
// '.html' để tách bạch path khỏi mọi route khác đã dùng cho Post ('{slug}-d{id}.html'), NHƯNG
// marker PHẢI khác ('-sk' thay vì '-d') — nếu dùng chung 'd' thì 2 route (Post/Event) có URI
// pattern giống nhau 100%, route đăng ký trước sẽ "nuốt" toàn bộ request khớp mẫu, phía sau
// không bao giờ tới được. {id} chỉ để phân biệt path, KHÔNG dùng để tra cứu — show() vẫn tra
// theo 'slug' như cũ, không cần sửa controller.
Route::get('{slug}-sk{id}.html', [PublicEventController::class, 'show'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])
    ->name('event.public.show');
