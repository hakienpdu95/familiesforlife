<?php

use Illuminate\Support\Facades\Route;
use Modules\KcItem\Http\Controllers\Api\KcAccessControlApiController;
use Modules\KcItem\Http\Controllers\Api\KcAttachmentApiController;
use Modules\KcItem\Http\Controllers\Api\KcFeedbackApiController;
use Modules\KcItem\Http\Controllers\Api\KcItemApiController;
use Modules\KcItem\Http\Controllers\Api\KcTagApiController;
use Modules\KcItem\Http\Controllers\KcAnalyticsController;
use Modules\KcItem\Http\Controllers\KcItemController;
use Modules\KcItem\Http\Controllers\KcProgressController;
use Modules\KcItem\Http\Controllers\KcTagController;

/*
|--------------------------------------------------------------------------
| KcItem Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD ─────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('kc-items', KcItemController::class);
    Route::resource('kc-tags', KcTagController::class);

    // Analytics dashboard
    Route::get('kc/analytics', [KcAnalyticsController::class, 'index'])->name('kc.analytics');

    // Status transitions
    Route::post('kc-items/{kc_item}/submit',  [KcItemController::class, 'submit'])->name('kc-items.submit');
    Route::post('kc-items/{kc_item}/approve', [KcItemController::class, 'approve'])->name('kc-items.approve');
    Route::post('kc-items/{kc_item}/reject',  [KcItemController::class, 'reject'])->name('kc-items.reject');
    Route::post('kc-items/{kc_item}/archive', [KcItemController::class, 'archive'])->name('kc-items.archive');

    // Version management
    Route::get('kc-items/{kc_item}/versions/{versionNumber}',
        [KcItemController::class, 'showVersion'])->name('kc-items.versions.show');
    Route::post('kc-items/{kc_item}/rollback/{versionNumber}',
        [KcItemController::class, 'rollback'])->name('kc-items.rollback');

    // Learning progress
    Route::post('kc-progress/{kcItem}/start',    [KcProgressController::class, 'start'])->name('kc-progress.start');
    Route::post('kc-progress/{kcItem}/complete', [KcProgressController::class, 'complete'])->name('kc-progress.complete');
});

// ── Backend JSON API ─────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('kc-items',  [KcItemApiController::class, 'index'])->name('kc-items');
    Route::get('kc-tags',   [KcTagApiController::class, 'index'])->name('kc-tags');
    Route::get('kc-tags/select', [KcTagApiController::class, 'forSelect'])->name('kc-tags.select');

    // Analytics API
    Route::get('kc/analytics/top-viewed',    [KcAnalyticsController::class, 'topViewed'])->name('kc.analytics.top-viewed');
    Route::get('kc/analytics/by-type',       [KcAnalyticsController::class, 'byType'])->name('kc.analytics.by-type');
    Route::get('kc/analytics/expiring-soon', [KcAnalyticsController::class, 'expiringSoon'])->name('kc.analytics.expiring-soon');
    Route::get('kc/analytics/unread',        [KcAnalyticsController::class, 'unread'])->name('kc.analytics.unread');

    // Attachments
    Route::post('kc-items/{kc_item}/attachments', [KcAttachmentApiController::class, 'store'])
        ->name('kc-items.attachments.store');
    Route::delete('kc-items/{kc_item}/attachments/{attachment}', [KcAttachmentApiController::class, 'destroy'])
        ->name('kc-items.attachments.destroy');

    // Access control
    Route::get('kc-items/{kc_item}/permissions',   [KcAccessControlApiController::class, 'index'])->name('kc-items.permissions.index');
    Route::post('kc-items/{kc_item}/permissions',  [KcAccessControlApiController::class, 'store'])->name('kc-items.permissions.store');
    Route::delete('kc-items/{kc_item}/permissions/{accessControl}', [KcAccessControlApiController::class, 'destroy'])->name('kc-items.permissions.destroy');

    // Feedback
    Route::post('kc-items/{kc_item}/feedback',         [KcFeedbackApiController::class, 'upsert'])->name('kc-items.feedback.upsert');
    Route::put('kc-items/{kc_item}/feedback',          [KcFeedbackApiController::class, 'upsert'])->name('kc-items.feedback.update');
    Route::get('kc-items/{kc_item}/feedback/summary',  [KcFeedbackApiController::class, 'summary'])->name('kc-items.feedback.summary');
    Route::get('kc-items/{kc_item}/feedback/mine',     [KcFeedbackApiController::class, 'myFeedback'])->name('kc-items.feedback.mine');
});
