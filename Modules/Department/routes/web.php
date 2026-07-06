<?php

use Illuminate\Support\Facades\Route;
use Modules\Department\Http\Controllers\Api\DepartmentApiController;
use Modules\Department\Http\Controllers\DepartmentController;

/*
|--------------------------------------------------------------------------
| Department Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('departments', DepartmentController::class);
});

// ── Backend JSON API for Tabulator ──────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('departments', [DepartmentApiController::class, 'index'])->name('departments');
});
