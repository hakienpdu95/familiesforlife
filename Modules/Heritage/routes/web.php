<?php

use Illuminate\Support\Facades\Route;
use Modules\Heritage\Features\HeritageSiteManagement\Http\HeritageSiteAdminController;
use Modules\Heritage\Features\HeritageSiteManagement\Http\HeritageSiteApiController;
use Modules\Heritage\Features\PublicReading\Http\PublicHeritageController;

// spec/Heritage_Technical_Specification.md §5.1 — dashboard/heritage/sites (resource, except
// show), cùng convention OcopProductAdminController.
Route::middleware(['auth'])->prefix('dashboard/heritage')->name('backend.heritage.')->group(function (): void {
    Route::resource('sites', HeritageSiteAdminController::class)->except(['show']);
});

// ── Backend JSON API cho Tabulator (session-based auth) ─────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api/heritage')->name('backend.api.heritage.')->group(function () {
    Route::get('sites', [HeritageSiteApiController::class, 'index'])->name('sites');
});

// ── Cổng thông tin công khai (§5.2) — KHÔNG yêu cầu đăng nhập, cùng convention Post/Event/Ocop. ──
Route::get('di-san', [PublicHeritageController::class, 'index'])->name('heritage.public.index');

// Chi tiết di tích: hậu tố '.html' — marker '-ds' (Di Sản) PHẢI khác '-d' (Post)/'-sk' (Event)/
// '-op' (Ocop) đã đăng ký trước, nếu không route đăng ký trước sẽ nuốt hết request khớp mẫu.
// {id} chỉ để phân biệt path, KHÔNG dùng để tra cứu — show() vẫn tra theo 'slug'.
Route::get('{slug}-ds{id}.html', [PublicHeritageController::class, 'show'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])
    ->name('heritage.public.show');
