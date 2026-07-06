<?php

use Illuminate\Support\Facades\Route;
use Modules\LeadPipelineStage\Http\Controllers\LeadPipelineStageController;

Route::middleware(['auth', 'verified'])
    ->prefix('leads/admin/pipeline-stages')
    ->name('lead-pipeline-stage.')
    ->group(function () {
        Route::get('/',              [LeadPipelineStageController::class, 'index'])->name('index');
        Route::get('/create',        [LeadPipelineStageController::class, 'create'])->name('create');
        Route::post('/',             [LeadPipelineStageController::class, 'store'])->name('store');
        Route::get('/{stage}/edit',  [LeadPipelineStageController::class, 'edit'])->name('edit');
        Route::put('/{stage}',       [LeadPipelineStageController::class, 'update'])->name('update');
        Route::delete('/{stage}',    [LeadPipelineStageController::class, 'destroy'])->name('destroy');
        Route::patch('/{stage}/toggle', [LeadPipelineStageController::class, 'toggle'])->name('toggle');
    });
