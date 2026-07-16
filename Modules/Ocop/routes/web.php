<?php

use Illuminate\Support\Facades\Route;
use Modules\Ocop\Features\OcopCategoryManagement\Http\OcopCategoryAdminController;
use Modules\Ocop\Features\OcopProductManagement\Http\OcopProductAdminController;
use Modules\Ocop\Features\PublicReading\Http\PublicOcopController;

// spec/Province_Showcase_Technical_Specification.md §6.1 — dashboard/ocop/products (resource,
// except show), cùng convention CategoryAdminController/BannerAdminController.
//
// dashboard/ocop/categories chỉ còn index (đọc) — spec/danhmuc.html là bảng phân loại sản phẩm
// OCOP chính thức (nhà nước quy định, thống nhất toàn quốc), đã chuẩn hóa qua OcopCategorySeeder,
// KHÔNG còn create/store/edit/update/destroy.
Route::middleware(['auth'])->prefix('dashboard/ocop')->name('backend.ocop.')->group(function (): void {
    Route::get('categories', [OcopCategoryAdminController::class, 'index'])->name('categories.index');
    Route::resource('products', OcopProductAdminController::class)->except(['show']);
});

// ── Cổng thông tin công khai (spec §8 DoD #5 — trang chi tiết sản phẩm OCOP) ────────────────
// KHÔNG yêu cầu đăng nhập, cùng convention Post/Event PublicReading. 'san-pham' (path tường
// minh) phải đăng ký TRƯỚC '{slug}' (wildcard) — cùng lý do đã áp dụng ở Post/Event routes.
Route::prefix('ocop')->name('ocop.public.')->group(function (): void {
    Route::get('/', [PublicOcopController::class, 'index'])->name('index');
    Route::get('{slug}', [PublicOcopController::class, 'show'])->name('show');
});
