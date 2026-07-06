<?php

use Illuminate\Support\Facades\Route;
use Modules\PerformanceReview\Http\Controllers\Api\PerformanceReviewApiController;
use Modules\PerformanceReview\Http\Controllers\PerformanceReviewController;
use Modules\PerformanceReview\Http\Controllers\ReviewTemplateController;

/*
|--------------------------------------------------------------------------
| PerformanceReview Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ─────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {

    Route::resource('performance-reviews', PerformanceReviewController::class);

    Route::post('performance-reviews/{performance_review}/finalize', [PerformanceReviewController::class, 'finalize'])
        ->name('performance-reviews.finalize');

    Route::post('performance-reviews/{performance_review}/submit', [PerformanceReviewController::class, 'submit'])
        ->name('performance-reviews.submit');

    Route::resource('review-templates', ReviewTemplateController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
});

// ── Backend JSON API for Tabulator ───────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('performance-reviews', [PerformanceReviewApiController::class, 'index'])->name('performance-reviews');
    Route::get('review-templates',    [ReviewTemplateController::class, 'apiIndex'])->name('review-templates');
});
