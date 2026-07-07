<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Features\CategoryManagement\Http\CategoryAdminController;
use Modules\Product\Features\CatalogManagement\Http\ProductAdminController;

// ── Catalog Sản phẩm & Dịch vụ — lấp route stub backend.products.* có sẵn ──────
Route::middleware(['auth', 'tenant'])
    ->prefix('dashboard/products')
    ->name('backend.products.')
    ->group(function (): void {
        Route::resource('categories', CategoryAdminController::class)->except(['show']);
        Route::post('categories/reorder', [CategoryAdminController::class, 'reorder'])->name('categories.reorder');

        // Route::resource('/', ...) không hỗ trợ path rỗng gọn gàng — viết tường minh
        // (docs/product-catalog-spec.md §12)
        Route::get('/', [ProductAdminController::class, 'index'])->name('index');
        Route::get('create', [ProductAdminController::class, 'create'])->name('create');
        Route::post('/', [ProductAdminController::class, 'store'])->name('store');
        Route::get('{product}/edit', [ProductAdminController::class, 'edit'])->name('edit');
        Route::put('{product}', [ProductAdminController::class, 'update'])->name('update');
        Route::delete('{product}', [ProductAdminController::class, 'destroy'])->name('destroy');
        Route::post('{product}/status', [ProductAdminController::class, 'changeStatus'])->name('change-status');
    });
