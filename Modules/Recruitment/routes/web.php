<?php

use Illuminate\Support\Facades\Route;
use Modules\Recruitment\Http\Controllers\Backend\ApplicationController;
use Modules\Recruitment\Http\Controllers\Backend\CandidateController;
use Modules\Recruitment\Http\Controllers\Backend\KanbanController;
use Modules\Recruitment\Http\Controllers\Backend\PipelineStageController;
use Modules\Recruitment\Http\Controllers\Api\ApplicationApiController;
use Modules\Recruitment\Http\Controllers\Api\CandidateApiController;
use Modules\Recruitment\Http\Controllers\Api\PipelineStageApiController;
use Modules\Recruitment\Http\Controllers\Backend\CandidateNoteController;
use Modules\Recruitment\Http\Controllers\Backend\CandidateAttachmentController;
use Modules\Recruitment\Http\Controllers\Backend\ImportController;
use Modules\Recruitment\Http\Controllers\Backend\InterviewController;
use Modules\Recruitment\Http\Controllers\Backend\EvaluationController;
use Modules\Recruitment\Http\Controllers\Backend\OfferController;
use Modules\Recruitment\Http\Controllers\Backend\AnalyticsController;

/*
|--------------------------------------------------------------------------
| Recruitment Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend views ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'feature:module.recruitment'])->prefix('dashboard/recruitment')->name('backend.recruitment.')->group(function () {

    // Import từ Marketplace — phải đặt TRƯỚC resource để không bị {candidate} capture
    Route::post('candidates/import-from-marketplace', [ImportController::class, 'fromMarketplace'])->name('candidates.import-marketplace');

    // Candidate management
    Route::resource('candidates', CandidateController::class);

    // Candidate notes
    Route::post('candidates/{candidate}/notes', [CandidateNoteController::class, 'store'])->name('candidates.notes.store');
    Route::delete('candidates/{candidate}/notes/{note}', [CandidateNoteController::class, 'destroy'])->name('candidates.notes.destroy');

    // Candidate attachments
    Route::post('candidates/{candidate}/attachments', [CandidateAttachmentController::class, 'store'])->name('candidates.attachments.store');
    Route::delete('candidates/{candidate}/attachments/{attachment}', [CandidateAttachmentController::class, 'destroy'])->name('candidates.attachments.destroy');

    // Application management
    Route::get('applications/create', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::post('applications/{application}/move', [ApplicationController::class, 'move'])->name('applications.move');
    Route::post('applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
    Route::patch('applications/{application}/assign', [ApplicationController::class, 'assign'])->name('applications.assign');

    // Interviews
    Route::get('interviews/my-schedule', [InterviewController::class, 'mySchedule'])->name('interviews.my-schedule');
    Route::get('interviews/create', [InterviewController::class, 'create'])->name('interviews.create');
    Route::post('applications/{application}/interviews', [InterviewController::class, 'store'])->name('applications.interviews.store');
    Route::get('interviews/{interview}', [InterviewController::class, 'show'])->name('interviews.show');
    Route::patch('interviews/{interview}/status', [InterviewController::class, 'updateStatus'])->name('interviews.update-status');

    // Evaluations
    Route::get('interviews/{interview}/evaluations/create', [EvaluationController::class, 'create'])->name('interviews.evaluations.create');
    Route::post('interviews/{interview}/evaluations', [EvaluationController::class, 'store'])->name('interviews.evaluations.store');
    Route::get('interviews/{interview}/evaluations/summary', [EvaluationController::class, 'summary'])->name('interviews.evaluations.summary');

    // Offers
    Route::get('offers/create', [OfferController::class, 'create'])->name('offers.create');
    Route::post('applications/{application}/offers', [OfferController::class, 'store'])->name('applications.offers.store');
    Route::get('offers/{offer}', [OfferController::class, 'show'])->name('offers.show');
    Route::post('offers/{offer}/submit-for-approval', [OfferController::class, 'submitForApproval'])->name('offers.submit-for-approval');
    Route::post('offers/{offer}/approve', [OfferController::class, 'approve'])->name('offers.approve');
    Route::post('offers/{offer}/send', [OfferController::class, 'send'])->name('offers.send');
    Route::post('offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
    Route::post('offers/{offer}/reject', [OfferController::class, 'reject'])->name('offers.reject');
    Route::post('offers/{offer}/revoke', [OfferController::class, 'revoke'])->name('offers.revoke');

    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/overview', [AnalyticsController::class, 'overview'])->name('analytics.overview');
    Route::get('analytics/funnel', [AnalyticsController::class, 'funnel'])->name('analytics.funnel');
    Route::get('analytics/time-to-hire', [AnalyticsController::class, 'timeToHire'])->name('analytics.time-to-hire');
    Route::get('analytics/source', [AnalyticsController::class, 'source'])->name('analytics.source');

    // Kanban board (by jp_job_post_uuid)
    Route::get('board/{jpJobPostUuid}', [KanbanController::class, 'show'])->name('board');

    // Pipeline stage config
    Route::prefix('pipeline-stages')->name('pipeline-stages.')->group(function () {
        Route::get('/', [PipelineStageController::class, 'index'])->name('index');
        Route::post('/', [PipelineStageController::class, 'store'])->name('store');
        Route::put('{pipelineStage}', [PipelineStageController::class, 'update'])->name('update');
    });

});

// ── Backend JSON APIs ─────────────────────────────────────────────────────
Route::middleware(['auth', 'feature:module.recruitment'])->prefix('backend/api/recruitment')->name('backend.api.recruitment.')->group(function () {

    Route::get('candidates', [CandidateApiController::class, 'index'])->name('candidates');
    Route::get('applications', [ApplicationApiController::class, 'index'])->name('applications');
    Route::get('jobs/{jpJobPostUuid}/board', [ApplicationApiController::class, 'board'])->name('board');
    Route::get('pipeline-stages', [PipelineStageApiController::class, 'index'])->name('pipeline-stages');
    Route::put('pipeline-stages/reorder', [PipelineStageApiController::class, 'reorder'])->name('pipeline-stages.reorder');

});
