<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\Http\Controllers\Api\ProjectOptionsController;
use Modules\Project\Http\Controllers\ProjectController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('projects/options', ProjectOptionsController::class)->name('projects.options');
    Route::apiResource('projects', ProjectController::class)->names('project');
});
