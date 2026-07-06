<?php

use Illuminate\Support\Facades\Route;
use Modules\OrgChart\Http\Controllers\Api\OrgChartApiController;
use Modules\OrgChart\Http\Controllers\OrgChartController;

/*
|--------------------------------------------------------------------------
| OrgChart Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ─────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('org-charts', OrgChartController::class)
        ->names('org-charts')
        ->parameters(['org-charts' => 'orgChartConfig']);
});

// ── Backend JSON API for Tabulator & tree rendering ──────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('org-charts', [OrgChartApiController::class, 'index'])->name('org-charts');
    Route::get('org-charts/{orgChartConfig}/tree', [OrgChartApiController::class, 'tree'])->name('org-charts.tree');
});
