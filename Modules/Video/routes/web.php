<?php

use Illuminate\Support\Facades\Route;
use Modules\Video\Features\PublicReading\Http\VideoPublicController;
use Modules\Video\Features\VideoManagement\Http\VideoAdminController;
use Modules\Video\Features\VideoManagement\Http\VideoApiController;

// ── Quản trị video (spec/Video_Management_Technical_Specification.md §6.4) ─────────────────
Route::middleware(['auth'])->prefix('dashboard/videos')->name('backend.video.')->group(function (): void {
    // ->except(['show']): không có trang xem chi tiết 1 video riêng ở khu vực quản trị
    // (cùng pattern Modules/Banner BannerAdminController).
    Route::resource('items', VideoAdminController::class)->except(['show'])->parameters(['items' => 'video']);
    Route::patch('items/{video}/toggle-active', [VideoAdminController::class, 'toggleActive'])->name('items.toggle-active');
});

// ── Backend JSON API cho Tabulator (session-based auth, cùng guard trang quản trị) ─────────
Route::middleware(['auth'])->prefix('backend/api/videos')->name('backend.api.videos.')->group(function (): void {
    Route::get('items', [VideoApiController::class, 'index'])->name('items');
});

// ── Trang công khai — thư viện video dạng lưới + lightbox (§7) ─────────────────────────────
Route::get('videos', [VideoPublicController::class, 'index'])->name('video.index');
