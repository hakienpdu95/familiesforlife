<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\Http\Controllers\Api\ProjectApiController;
use Modules\Project\Http\Controllers\ProjectController;

/*
|--------------------------------------------------------------------------
| Project Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('projects', ProjectController::class);
});

// ── Backend JSON API for Tabulator ──────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('projects', [ProjectApiController::class, 'index'])->name('projects');
});
