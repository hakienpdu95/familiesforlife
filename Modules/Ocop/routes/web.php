<?php

use Illuminate\Support\Facades\Route;
use Modules\Ocop\Features\OcopCategoryManagement\Http\OcopCategoryAdminController;
use Modules\Ocop\Features\OcopProductManagement\Http\OcopProductAdminController;
use Modules\Ocop\Features\OcopProductManagement\Http\OcopProductApiController;
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

// ── Backend JSON API cho Tabulator (session-based auth, cùng guard trang quản trị) —
// tham chiếu Modules/Organization/routes/web.php (backend.api.organizations) ───────────────
Route::middleware(['auth'])->prefix('backend/api/ocop')->name('backend.api.ocop.')->group(function () {
    Route::get('products', [OcopProductApiController::class, 'index'])->name('products');
});

// ── Cổng thông tin công khai (spec §8 DoD #5 — trang chi tiết sản phẩm OCOP) ────────────────
// KHÔNG yêu cầu đăng nhập, cùng convention Post/Event PublicReading.
Route::get('ocop', [PublicOcopController::class, 'index'])->name('ocop.public.index');

// Chi tiết sản phẩm: bỏ prefix 'ocop', ra root '{slug}-op{id}.html' — cùng cơ chế hậu tố '.html'
// đã áp dụng cho Post ('{slug}-d{id}.html') và Event ('{slug}-sk{id}.html'). Marker '-op' (OCOP
// Product) PHẢI khác 2 marker trên — nếu trùng, route đăng ký trước sẽ nuốt hết request khớp
// mẫu, route đăng ký sau (module này) sẽ không bao giờ được gọi tới. {id} chỉ để phân biệt
// path, KHÔNG dùng để tra cứu — show() vẫn tra theo 'slug' như cũ, không cần sửa controller.
Route::get('{slug}-op{id}.html', [PublicOcopController::class, 'show'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])
    ->name('ocop.public.show');
