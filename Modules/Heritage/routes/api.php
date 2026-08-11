<?php

use Illuminate\Support\Facades\Route;
use Modules\Heritage\Features\HeritageSiteManagement\Actions\ListHeritageSitesForPickerAction;

/*
|--------------------------------------------------------------------------
| Heritage Module — API Routes  (prefix: /api)
|--------------------------------------------------------------------------
*/

// ── Reference data (public — di tích published, cùng convention ocop-products/picker) ───────
Route::get('/heritage-sites/picker', ListHeritageSitesForPickerAction::class)
    ->name('heritage-sites.picker');
