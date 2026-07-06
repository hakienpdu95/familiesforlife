<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\OrganizationApiController;
use Modules\Organization\Http\Controllers\Api\OrganizationLogoController;
use Modules\Organization\Http\Controllers\OrganizationController;
use Modules\Organization\Http\Controllers\OrganizationVerticalController;

/*
|--------------------------------------------------------------------------
| Organization Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD (admin panel — System_Admin / super-admin only) ───────────
// Authorization được enforce qua OrganizationPolicy::authorizeResource() trong constructor.
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('organizations', OrganizationController::class);

    // ── Vertical management per org ───────────────────────────────────────────
    Route::prefix('organizations/{organization}/verticals')
        ->name('organizations.verticals.')
        ->group(function () {
            Route::get('/',                  [OrganizationVerticalController::class, 'index'])->name('index');
            Route::get('/create',            [OrganizationVerticalController::class, 'createFromScratch'])->name('create');
            Route::post('/create',           [OrganizationVerticalController::class, 'storeFromScratch'])->name('store');
            Route::post('/{code}/activate',  [OrganizationVerticalController::class, 'activate'])->name('activate');
            Route::delete('/{code}',         [OrganizationVerticalController::class, 'deactivate'])->name('deactivate');
            Route::get('/{code}/config',     [OrganizationVerticalController::class, 'config'])->name('config');
            Route::patch('/{code}/config',   [OrganizationVerticalController::class, 'updateConfig'])->name('updateConfig');
            Route::post('/{code}/config/items',          [OrganizationVerticalController::class, 'storeConfigItem'])->name('config.items.store');
            Route::delete('/{code}/config/items/{item}', [OrganizationVerticalController::class, 'destroyConfigItem'])->name('config.items.destroy');
            Route::get('/{code}/preview',    [OrganizationVerticalController::class, 'previewDashboard'])->name('preview');
        });
});

// ── Backend JSON API for Tabulator (session-based auth, same guard as admin panel) ─────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('organizations', [OrganizationApiController::class, 'index'])
        ->name('organizations');

    // Logo upload / delete
    Route::post('organizations/{organization}/logo',   [OrganizationLogoController::class, 'store'])->name('organizations.logo.store');
    Route::delete('organizations/{organization}/logo', [OrganizationLogoController::class, 'destroy'])->name('organizations.logo.destroy');
});
