<?php

use Illuminate\Support\Facades\Route;
use Modules\OrgChart\Http\Controllers\OrgChartController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('orgcharts', OrgChartController::class)->names('orgchart');
});
