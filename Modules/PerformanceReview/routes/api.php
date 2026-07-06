<?php

use Illuminate\Support\Facades\Route;
use Modules\PerformanceReview\Http\Controllers\PerformanceReviewController;
use Modules\PerformanceReview\Http\Controllers\ReviewTemplateController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('reviewtemplates/options', [ReviewTemplateController::class, 'options'])->name('reviewtemplate.options');
    Route::apiResource('performancereviews', PerformanceReviewController::class)->names('performancereview');
});
