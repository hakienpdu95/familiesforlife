<?php

use Illuminate\Support\Facades\Route;
use Modules\AccessTrade\Features\OfferManagement\Http\OfferAdminController;
use Modules\AccessTrade\Features\OfferManagement\Http\OfferApiController;
use Modules\AccessTrade\Features\Sync\Http\SyncTriggerController;
use Modules\AccessTrade\Features\TopProductManagement\Http\TopProductAdminController;
use Modules\AccessTrade\Features\TopProductManagement\Http\TopProductApiController;

// ── Quản trị AccessTrade — dữ liệu chỉ đọc, đồng bộ từ AccessTrade Publisher API. Permission
// phẳng 'accesstrade.manage' (Lớp B — Modules\AccessTrade\Database\Seeders\AccessTradePermissionSeeder),
// KHÔNG qua config/permissions.php, cùng nguyên tắc Banner/Ocop/CoreIdeaExtractor. ────────────
Route::middleware(['auth', 'can:accesstrade.manage'])->prefix('dashboard/accesstrade')->name('backend.accesstrade.')->group(function (): void {
    Route::get('offers', [OfferAdminController::class, 'index'])->name('offers.index');
    Route::get('top-products', [TopProductAdminController::class, 'index'])->name('top-products.index');
    Route::post('sync', [SyncTriggerController::class, 'store'])->name('sync');
});

// ── Backend JSON API cho Tabulator (session-based auth, cùng guard trang quản trị) ──────────
Route::middleware(['auth', 'can:accesstrade.manage'])->prefix('backend/api/accesstrade')->name('backend.api.accesstrade.')->group(function (): void {
    Route::get('offers', [OfferApiController::class, 'index'])->name('offers');
    Route::get('top-products', [TopProductApiController::class, 'index'])->name('top-products');
});
