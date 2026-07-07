<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Features\CatalogPicker\Http\ProductPickerApiController;

// ── Slice CatalogPicker — nội bộ, gọi bởi dialog Jodit "Sản phẩm" của Modules\Post
// (docs/product-catalog-spec.md §10.3/§12) ─────────────────────────────────
Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1/products')->group(function (): void {
    Route::get('search', [ProductPickerApiController::class, 'search']);
    Route::get('batch', [ProductPickerApiController::class, 'batch']);
});
