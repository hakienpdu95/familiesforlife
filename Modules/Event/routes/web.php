<?php

use Illuminate\Support\Facades\Route;
use Modules\Event\Features\EventCategoryManagement\Http\EventCategoryAdminController;
use Modules\Event\Features\EventModeration\Http\EventAdminController;
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

// ── Cổng thông tin công khai (spec §8/§13 Phase 3) — không {locale}, cùng quyết định đã áp
// dụng cho Post. THỨ TỰ quan trọng: 'gui-su-kien' (nộp sự kiện) và 'danh-muc/{category:slug}'
// là path tường minh, phải đăng ký TRƯỚC 'su-kien/{slug}' (wildcard) — Laravel so khớp route
// theo đúng thứ tự đăng ký, không tự ưu tiên path cụ thể hơn wildcard (cùng lý do đã áp dụng ở
// Post routes/web.php: articles/pending-review trước articles/{article}).
Route::get('event-sitemap.xml', [EventSitemapController::class, 'index'])->name('event.public.sitemap');

Route::prefix('su-kien')->group(function (): void {
    Route::get('/', [PublicEventController::class, 'index'])->name('event.public.home');
    Route::get('danh-muc/{category:slug}', [PublicEventController::class, 'category'])->name('event.public.category');

    Route::prefix('gui-su-kien')->name('event.public.submit.')->group(function (): void {
        Route::get('/', [EventSubmissionController::class, 'create'])->name('form');
        Route::post('/', [EventSubmissionController::class, 'store'])
            ->middleware(['throttle:event-submit', ValidateEventTurnstile::class])
            ->name('store');
        Route::get('thanh-cong', [EventSubmissionController::class, 'success'])->name('success');
    });

    Route::get('{slug}', [PublicEventController::class, 'show'])->name('event.public.show');
});
