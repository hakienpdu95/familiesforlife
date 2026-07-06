<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\EmployeeApiController;
use Modules\Employee\Http\Controllers\EmployeeController;

/*
|--------------------------------------------------------------------------
| Employee Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('employees', EmployeeController::class);
});

// ── Backend JSON API for Tabulator ──────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('employees', [EmployeeApiController::class, 'index'])->name('employees');
});
