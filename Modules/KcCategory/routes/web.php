<?php

use Illuminate\Support\Facades\Route;
use Modules\KcCategory\Http\Controllers\Api\KcCategoryApiController;
use Modules\KcCategory\Http\Controllers\KcCategoryController;

/*
|--------------------------------------------------------------------------
| KcCategory Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ─────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('kc-categories', KcCategoryController::class);
});

// ── Backend JSON API for Tabulator ───────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('kc-categories', [KcCategoryApiController::class, 'index'])->name('kc-categories');
});
