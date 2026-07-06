<?php

use Illuminate\Support\Facades\Route;
use Modules\LeadPipelineStage\Http\Controllers\Api\LeadPipelineStageApiController;

Route::middleware(['auth:sanctum', 'tenant'])
    ->prefix('v1/lead-pipeline-stages')
    ->name('lead-pipeline-stage.')
    ->group(function () {
        Route::get('/', [LeadPipelineStageApiController::class, 'list'])->name('list');
    });
