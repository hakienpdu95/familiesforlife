<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\UserApiController;
use Modules\User\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| User Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD (admin panel) ─────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
});

// ── Backend JSON API for Tabulator ────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('users', [UserApiController::class, 'index'])->name('users');
});
