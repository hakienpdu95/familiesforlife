<?php

use Illuminate\Support\Facades\Route;
use Modules\Banner\Features\BannerManagement\Http\BannerAdminController;
use Modules\Banner\Features\BannerManagement\Http\BannerApiController;
use Modules\Banner\Features\PublicReading\Http\BannerClickController;

// ── Quản trị banner (spec/Banner_Management_Technical_Specification.md §6.1) ────────────────
Route::middleware(['auth'])->prefix('dashboard/banners')->name('backend.banner.')->group(function (): void {
    // ->except(['show']): không có trang xem chi tiết 1 banner riêng (cùng pattern
    // Modules/Post CategoryAdminController/Modules/Menu MenuItemAdminController).
    Route::resource('items', BannerAdminController::class)->except(['show'])->parameters(['items' => 'banner']);
});

// ── Backend JSON API cho Tabulator (session-based auth, cùng guard trang quản trị) —
// tham chiếu Modules/Organization/routes/web.php (backend.api.organizations) ───────────────
Route::middleware(['auth'])->prefix('backend/api/banners')->name('backend.api.banners.')->group(function () {
    Route::get('items', [BannerApiController::class, 'index'])->name('items');
});

// ── Đếm click (công khai, không yêu cầu đăng nhập) — spec §5.4 ──────────────────────────────
Route::get('banners/{banner:uuid}/click', [BannerClickController::class, 'redirect'])->name('banner.click');
