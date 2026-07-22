<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Page\Features\PageManagement\Http\PageAdminController;
use Modules\Page\Features\PageManagement\Http\PageApiController;
use Modules\Page\Features\PublicReading\Http\PagePublicController;
use Modules\Page\Models\Page;

/*
|--------------------------------------------------------------------------
| Quản trị trang tĩnh
| spec/Page_Static_Pages_Technical_Specification.md §4.1
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('dashboard/pages')->name('backend.page.')->group(function (): void {
    // ->except(['show']): không có trang xem chi tiết riêng, cùng pattern Menu/Banner.
    Route::resource('items', PageAdminController::class)->except(['show'])->parameters(['items' => 'page']);
    Route::patch('items/{page}/publish', [PageAdminController::class, 'publish'])->name('items.publish');
    Route::patch('items/{page}/unpublish', [PageAdminController::class, 'unpublish'])->name('items.unpublish');
});

// ── Backend JSON API cho Tabulator (session-based auth, cùng guard trang quản trị) —
// tham chiếu Modules/Organization/routes/web.php (backend.api.organizations) ───────────────
Route::middleware(['auth'])->prefix('backend/api/pages')->name('backend.api.pages.')->group(function () {
    Route::get('items', [PageApiController::class, 'index'])->name('items');
});

/*
|--------------------------------------------------------------------------
| Công khai — URL gốc (abc.com/{slug}), KHÔNG tiền tố.
| spec/Page_Static_Pages_Technical_Specification.md §4.1 — Route::fallback() chỉ chạy khi
| KHÔNG route nào khác trong toàn app khớp, bất kể thứ tự đăng ký route giữa các module.
| Lớp validate reserved_slugs (PageAdminController::validated()) là lớp chặn trùng ở NGUỒN,
| độc lập với lớp này — không lớp nào thay thế lớp kia.
|--------------------------------------------------------------------------
*/
Route::fallback(function (Request $request) {
    if ($request->method() !== 'GET') {
        abort(404);
    }

    $slug = trim($request->path(), '/');
    $page = Page::published()->where('slug', $slug)->first();

    abort_unless($page, 404);

    return app(PagePublicController::class)($page);
})->name('page.public.fallback');
