<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Actions\GetWardsByProvinceAction;

/*
|--------------------------------------------------------------------------
| Organization Module — API Routes  (prefix: /api)
|--------------------------------------------------------------------------
*/

// ── Reference data (public — không cần auth) ──────────────────────────
Route::get('/provinces/{provinceCode}/wards', GetWardsByProvinceAction::class)
    ->name('provinces.wards');
