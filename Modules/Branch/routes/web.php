<?php

use Illuminate\Support\Facades\Route;
use Modules\Branch\Http\Controllers\Api\BranchApiController;
use Modules\Branch\Http\Controllers\BranchController;

/*
|--------------------------------------------------------------------------
| Branch Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('branches', BranchController::class);
});

// ── Backend JSON API for Tabulator ──────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('branches', [BranchApiController::class, 'index'])->name('branches');
});
