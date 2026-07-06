<?php

use Illuminate\Support\Facades\Route;
use Modules\Sop\Http\Controllers\Api\SopApiController;
use Modules\Sop\Http\Controllers\SopFlowchartController;
use Modules\Sop\Http\Controllers\SopProcessController;
use Modules\Sop\Http\Controllers\SopStepAttachmentController;
use Modules\Sop\Http\Controllers\SopStepConnectorController;
use Modules\Sop\Http\Controllers\SopStepController;
use Modules\Sop\Http\Controllers\SopStepRaciController;
use Modules\Sop\Http\Controllers\SopApprovalController;
use Modules\Sop\Http\Controllers\SopRelationController;
use Modules\Sop\Http\Controllers\SopStepReorderController;
use Modules\Sop\Http\Controllers\SopAnalyticsController;
use Modules\Sop\Http\Controllers\SopExportController;
use Modules\Sop\Http\Controllers\SopVersionController;

Route::middleware(['auth', 'web', 'feature:module.sop'])->group(function () {

    // ── API (JSON) ───────────────────────────────────────────────────────────
    Route::prefix('backend/api')->name('backend.api.')->group(function () {
        Route::get('sop', [SopApiController::class, 'index'])->name('sop');
        Route::get('sop/approved', [SopApiController::class, 'approved'])->name('sop.approved');
        Route::get('sop/search', [SopApiController::class, 'search'])->name('sop.search');

        // Relations
        Route::post('sop/{sop}/relations', [SopRelationController::class, 'store'])->name('sop.relations.store');
        Route::delete('sop/{sop}/relations/{relation}', [SopRelationController::class, 'destroy'])->name('sop.relations.destroy');

        // Read-only
        Route::get('sop-steps/{step}/raci', [SopStepRaciController::class, 'index'])->name('sop.steps.raci.index');
        Route::get('sop-steps/{step}/attachments', [SopStepAttachmentController::class, 'index'])->name('sop.steps.attachments.index');
        Route::get('sop/{sop}/versions/{version}/flowchart-data', [SopVersionController::class, 'flowchartData'])->name('sop.versions.flowchart-data');
        Route::get('users/search', [SopApiController::class, 'usersSearch'])->name('users.search');
        Route::get('roles', [SopApiController::class, 'roles'])->name('roles');

        // Editor mutation endpoints — rate-limited: 60 requests/minute per user
        Route::middleware('throttle:60,1')->group(function () {
            // Step CRUD — reorder must be before {step} to avoid routing conflict
            Route::put('sop/{sop}/steps/reorder', [SopStepReorderController::class, 'update'])->name('sop.steps.reorder');
            Route::post('sop/{sop}/steps', [SopStepController::class, 'store'])->name('sop.steps.store');
            Route::put('sop/{sop}/steps/{step}', [SopStepController::class, 'update'])->name('sop.steps.update');
            Route::delete('sop/{sop}/steps/{step}', [SopStepController::class, 'destroy'])->name('sop.steps.destroy');

            // Connector CRUD
            Route::post('sop/{sop}/connectors', [SopStepConnectorController::class, 'store'])->name('sop.connectors.store');
            Route::put('sop/{sop}/connectors/{connector}', [SopStepConnectorController::class, 'update'])->name('sop.connectors.update');
            Route::delete('sop/{sop}/connectors/{connector}', [SopStepConnectorController::class, 'destroy'])->name('sop.connectors.destroy');

            // RACI mutations
            Route::post('sop-steps/{step}/raci', [SopStepRaciController::class, 'store'])->name('sop.steps.raci.store');
            Route::delete('sop-steps/{step}/raci/{raci}', [SopStepRaciController::class, 'destroy'])->name('sop.steps.raci.destroy');

            // Attachment mutations
            Route::post('sop-steps/{step}/attachments', [SopStepAttachmentController::class, 'store'])->name('sop.steps.attachments.store');
            Route::delete('sop-steps/{step}/attachments/{attachment}', [SopStepAttachmentController::class, 'destroy'])->name('sop.steps.attachments.destroy');

            // Approval workflow
            Route::post('sop/{sop}/submit-review', [SopApprovalController::class, 'submitReview'])->name('sop.submit-review');
            Route::post('sop/approval-flows/{flow}/approve', [SopApprovalController::class, 'approve'])->name('sop.approval.approve');
            Route::post('sop/approval-flows/{flow}/reject',  [SopApprovalController::class, 'reject'])->name('sop.approval.reject');

            // Rollback
            Route::post('sop/{sop}/rollback/{version}', [SopVersionController::class, 'rollback'])->name('sop.rollback');
        });
    });

    // ── Web (Blade) ──────────────────────────────────────────────────────────
    Route::prefix('dashboard/sop')->name('backend.sop.')->group(function () {
        Route::get('/',                [SopProcessController::class,   'index'])->name('index');
        Route::get('/create',          [SopProcessController::class,   'create'])->name('create');
        Route::post('/',               [SopProcessController::class,   'store'])->name('store');
        Route::get('/analytics',       [SopAnalyticsController::class, 'dashboard'])->name('analytics');

        // Pending approvals — must be before /{sop} to avoid conflict
        Route::get('/pending-approvals', [SopApprovalController::class, 'pendingList'])->name('pending-approvals');

        Route::get('/{sop}',                   [SopProcessController::class,   'show'])->name('show');
        Route::get('/{sop}/edit',              [SopProcessController::class,   'edit'])->name('edit');
        Route::put('/{sop}',                   [SopProcessController::class,   'update'])->name('update');
        Route::delete('/{sop}',                [SopProcessController::class,   'destroy'])->name('destroy');
        Route::get('/{sop}/flowchart/data',    [SopFlowchartController::class, 'data'])->name('flowchart.data');
        Route::get('/{sop}/raci',              [SopProcessController::class,   'raciMatrix'])->name('raci');

        // Export & Print
        Route::get('/{sop}/export/pdf', [SopExportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/{sop}/export/png', [SopExportController::class, 'exportPng'])->name('export.png');
        Route::get('/{sop}/print',      [SopExportController::class, 'printView'])->name('print');

        // Version review page
        Route::get('/{sop}/versions/{version}/review', [SopVersionController::class, 'review'])->name('versions.review');
    });
});
