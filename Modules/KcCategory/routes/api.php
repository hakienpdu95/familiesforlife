<?php

use Illuminate\Support\Facades\Route;
use Modules\KcCategory\Http\Controllers\KcCategoryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('kc-categories/options', [KcCategoryController::class, 'options'])->name('kc-category.options');
});
