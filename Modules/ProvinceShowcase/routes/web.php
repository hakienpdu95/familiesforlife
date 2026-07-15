<?php

use Illuminate\Support\Facades\Route;
use Modules\ProvinceShowcase\Features\PublicShowcase\Http\ProvincePublicController;

/**
 * spec/Province_Showcase_Technical_Specification.md §7.1 — route công khai, không có admin CRUD
 * (§6.2). show() tự resolve theo slug thủ công (không dùng implicit route-model-binding —
 * App\Models\Province đang được dùng ở nhiều nơi bind theo id/province_code, không nên override
 * getRouteKeyName() toàn cục).
 *
 * {type} lấy trực tiếp từ provinces.place_type ('tinh'|'thanh-pho' — 2 giá trị enum duy nhất,
 * xem migration create_provinces_table) — Huế là "Thành phố Trung Ương" (place_type=thanh-pho)
 * nên phải là /thanh-pho/hue, không phải /tinh/hue; Cà Mau (place_type=tinh) là /tinh/ca-mau.
 * Controller đối chiếu $type với $province->place_type thật, sai prefix (vd /tinh/hue) → 404,
 * tránh 2 URL cùng trỏ 1 nội dung.
 */
Route::name('province.public.')->group(function (): void {
    Route::get('dia-phuong', [ProvincePublicController::class, 'index'])->name('index');
    Route::get('{type}/{slug}', [ProvincePublicController::class, 'show'])
        ->whereIn('type', ['tinh', 'thanh-pho'])
        ->name('show');
});
