<?php

use Illuminate\Support\Facades\Route;
use Modules\JobTitle\Http\Controllers\Api\JobTitleApiController;
use Modules\JobTitle\Http\Controllers\JobTitleController;

/*
|--------------------------------------------------------------------------
| JobTitle Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('job-titles', JobTitleController::class);
});

// ── Backend JSON API for Tabulator ──────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('job-titles', [JobTitleApiController::class, 'index'])->name('job-titles');
});
