<?php

use Illuminate\Support\Facades\Route;
use Modules\JobPosting\Http\Controllers\Api\JpBenefitMasterApiController;
use Modules\JobPosting\Http\Controllers\Api\JpCareerApiController;
use Modules\JobPosting\Http\Controllers\Api\JpJobPostAnalyticsApiController;
use Modules\JobPosting\Http\Controllers\Api\JpJobPostApiController;
use Modules\JobPosting\Http\Controllers\Api\JpJobPostBenefitApiController;
use Modules\JobPosting\Http\Controllers\Api\JpJobPostSkillApiController;
use Modules\JobPosting\Http\Controllers\Api\JpScreeningQuestionApiController;
use Modules\JobPosting\Http\Controllers\Api\JpSkillMasterApiController;
use Modules\JobPosting\Http\Controllers\JpJobPostController;

/*
|--------------------------------------------------------------------------
| JobPosting Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('job-posts', JpJobPostController::class);
    Route::post('job-posts/{job_post}/publish',        [JpJobPostController::class, 'publish'])->name('job-posts.publish');
    Route::post('job-posts/{job_post}/close',          [JpJobPostController::class, 'close'])->name('job-posts.close');
    Route::post('job-posts/{job_post}/submit-review',  [JpJobPostController::class, 'submitReview'])->name('job-posts.submit-review');
    Route::post('job-posts/{job_post}/pause',          [JpJobPostController::class, 'pause'])->name('job-posts.pause');
    Route::post('job-posts/{job_post}/archive',        [JpJobPostController::class, 'archive'])->name('job-posts.archive');
    Route::post('job-posts/{job_post}/duplicate',      [JpJobPostController::class, 'duplicate'])->name('job-posts.duplicate');
    Route::post('job-posts/{job_post}/sync-marketplace',[JpJobPostController::class, 'syncMarketplace'])->name('job-posts.sync-marketplace');
});

// ── Backend JSON API ────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {

    // List posts (Tabulator)
    Route::get('job-posts', [JpJobPostApiController::class, 'index'])->name('job-posts');

    // Analytics & history per post
    Route::get('job-posts/{job_post}/analytics', [JpJobPostAnalyticsApiController::class, 'analytics'])->name('job-posts.analytics');
    Route::get('job-posts/{job_post}/history',   [JpJobPostAnalyticsApiController::class, 'history'])->name('job-posts.history');

    // Master lookups
    Route::get('skill-masters',   [JpSkillMasterApiController::class,   'index'])->name('skill-masters');
    Route::get('benefit-masters', [JpBenefitMasterApiController::class, 'index'])->name('benefit-masters');

    // Skills per post
    Route::get   ('job-posts/{job_post}/skills',          [JpJobPostSkillApiController::class, 'index'])->name('job-posts.skills.index');
    Route::post  ('job-posts/{job_post}/skills',          [JpJobPostSkillApiController::class, 'store'])->name('job-posts.skills.store');
    Route::put   ('job-posts/{job_post}/skills/{skill}',  [JpJobPostSkillApiController::class, 'update'])->name('job-posts.skills.update');
    Route::delete('job-posts/{job_post}/skills/{skill}',  [JpJobPostSkillApiController::class, 'destroy'])->name('job-posts.skills.destroy');

    // Benefits per post
    Route::get   ('job-posts/{job_post}/benefits',            [JpJobPostBenefitApiController::class, 'index'])->name('job-posts.benefits.index');
    Route::post  ('job-posts/{job_post}/benefits',            [JpJobPostBenefitApiController::class, 'store'])->name('job-posts.benefits.store');
    Route::delete('job-posts/{job_post}/benefits/{benefit}',  [JpJobPostBenefitApiController::class, 'destroy'])->name('job-posts.benefits.destroy');

    // Screening questions per post (reorder BEFORE {question} to avoid param collision)
    Route::put   ('job-posts/{job_post}/questions/reorder',      [JpScreeningQuestionApiController::class, 'reorder'])->name('job-posts.questions.reorder');
    Route::get   ('job-posts/{job_post}/questions',              [JpScreeningQuestionApiController::class, 'index'])->name('job-posts.questions.index');
    Route::post  ('job-posts/{job_post}/questions',              [JpScreeningQuestionApiController::class, 'store'])->name('job-posts.questions.store');
    Route::put   ('job-posts/{job_post}/questions/{question}',   [JpScreeningQuestionApiController::class, 'update'])->name('job-posts.questions.update');
    Route::delete('job-posts/{job_post}/questions/{question}',   [JpScreeningQuestionApiController::class, 'destroy'])->name('job-posts.questions.destroy');
});

// ── Public Career Page API (no auth) ────────────────────────────────────────
Route::prefix('api/careers')->name('careers.')->group(function () {
    Route::get   ('{org_slug}/jobs',                 [JpCareerApiController::class, 'index'])->name('jobs.index');
    Route::get   ('{org_slug}/jobs/{slug}',          [JpCareerApiController::class, 'show'])->name('jobs.show');
    Route::get   ('{org_slug}/jobs/{slug}/questions',[JpCareerApiController::class, 'questions'])->name('jobs.questions');
    Route::post  ('{org_slug}/jobs/{slug}/apply',    [JpCareerApiController::class, 'apply'])->name('jobs.apply');
});
