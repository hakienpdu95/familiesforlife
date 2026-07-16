<?php

use Illuminate\Support\Facades\Route;
use Modules\Ocop\Features\OcopProductManagement\Actions\ListOcopProductsForPickerAction;

/*
|--------------------------------------------------------------------------
| Ocop Module — API Routes  (prefix: /api)
|--------------------------------------------------------------------------
*/

// ── Reference data (public — sản phẩm published, cùng convention provinces/wards) ─────
Route::get('/ocop-products/picker', ListOcopProductsForPickerAction::class)
    ->name('ocop-products.picker');
