<?php

use Illuminate\Support\Facades\Route;
use Modules\Menu\Features\MenuManagement\Http\MenuItemAdminController;

/*
|--------------------------------------------------------------------------
| Quản trị điều hướng menu — Phase 1
| spec/Menu_Navigation_Technical_Specification.md §6.1/§8 — route công khai (render
| header/footer/drawer) là Phase 3, CHƯA có ở đây.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('dashboard/menu')->name('backend.menu.')->group(function () {
    // ->except(['show']): không có trang xem chi tiết 1 mục menu riêng (cùng pattern
    // Modules/Post CategoryAdminController — sửa/xoá đủ dùng, không cần trang show).
    Route::resource('items', MenuItemAdminController::class)->except(['show'])->parameters(['items' => 'menuItem']);
    Route::post('items/reorder', [MenuItemAdminController::class, 'reorder'])->name('items.reorder');
});
