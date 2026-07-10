<?php

use Illuminate\Support\Facades\Route;
use Modules\Aicem\Features\Dashboard\Http\AicemDashboardController;
use Modules\Aicem\Features\ExampleLearning\Http\ExampleCandidateAdminController;
use Modules\Aicem\Features\Generation\Http\AicemGenerationController;
use Modules\Aicem\Features\KnowledgeBase\Http\KnowledgeDocumentAdminController;

Route::middleware(['auth', 'tenant'])
    ->prefix('dashboard/aicem')
    ->name('backend.aicem.')
    ->group(function (): void {
        Route::resource('knowledge-documents', KnowledgeDocumentAdminController::class)
            ->except(['show'])
            ->parameters(['knowledge-documents' => 'document']);

        Route::post('knowledge-documents/{document}/versions/{version}/restore', [KnowledgeDocumentAdminController::class, 'restoreVersion'])
            ->name('knowledge-documents.versions.restore');

        // Panel AI (mục 9) — POST tạo run chạy nền, GET status poll, accept/reject suggestion.
        // {run} tenant-scoped qua route model binding (AicemGenerationRun extends TenantAwareModel);
        // {suggestion} KHÔNG tenant-scoped (mục 7) nên luôn kiểm generation_run_id === $run->id
        // trong controller trước khi thao tác — tránh truy cập chéo suggestion của run khác.
        Route::post('generation/run', [AicemGenerationController::class, 'run'])->name('generation.run');
        Route::get('generation/runs/{run}/status', [AicemGenerationController::class, 'status'])->name('generation.status');
        Route::post('generation/runs/{run}/suggestions/{suggestion}/accept', [AicemGenerationController::class, 'acceptSuggestion'])->name('generation.suggestions.accept');
        Route::post('generation/runs/{run}/suggestions/{suggestion}/reject', [AicemGenerationController::class, 'rejectSuggestion'])->name('generation.suggestions.reject');

        // Dashboard (Phase 4, mục 15) — overview cho ai có bất kỳ quyền aicem nào, settings
        // (BYOK/hạn mức/rate-limit) chỉ System Admin (aicem.config).
        Route::get('dashboard', [AicemDashboardController::class, 'overview'])->name('dashboard');
        Route::get('settings', [AicemDashboardController::class, 'settings'])->name('settings');
        Route::put('settings', [AicemDashboardController::class, 'updateSettings'])->name('settings.update');

        // Phase 5 (tuỳ chọn, mục 11/15) — duyệt candidate example_good tự động đề xuất từ bài
        // viết published is_featured=true.
        Route::get('example-candidates', [ExampleCandidateAdminController::class, 'index'])->name('example-candidates.index');
        Route::post('example-candidates/{candidate}/approve', [ExampleCandidateAdminController::class, 'approve'])->name('example-candidates.approve');
        Route::post('example-candidates/{candidate}/reject', [ExampleCandidateAdminController::class, 'reject'])->name('example-candidates.reject');
    });
