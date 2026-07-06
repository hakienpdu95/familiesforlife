<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleScope\Http\Controllers\Api\RoleScopeApiController;
use Modules\RoleScope\Http\Controllers\RoleScopeController;

/*
|--------------------------------------------------------------------------
| RoleScope Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    // Must be before resource to avoid {role_scope} binding capturing "api"
    Route::get('role-scopes/api/org-data', [RoleScopeController::class, 'apiOrgData'])
        ->name('role-scopes.api.org-data');

    Route::resource('role-scopes', RoleScopeController::class);
});

// ── Backend JSON API for Tabulator ──────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('role-scopes', [RoleScopeApiController::class, 'index'])->name('role-scopes');
});
