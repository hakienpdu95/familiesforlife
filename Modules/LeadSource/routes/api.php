<?php

use Illuminate\Support\Facades\Route;
use Modules\LeadSource\Http\Controllers\Api\LeadSourceApiController;

Route::middleware(['auth:sanctum', 'tenant'])
    ->prefix('v1/lead-sources')
    ->name('lead-source.')
    ->group(function () {
        Route::get('/', [LeadSourceApiController::class, 'list'])->name('list');
    });
