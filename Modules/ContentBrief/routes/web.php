<?php

use Illuminate\Support\Facades\Route;
use Modules\ContentBrief\Features\BriefManagement\Http\BriefAdminController;
use Modules\ContentBrief\Features\Generation\Http\BriefGenerationController;

/*
|--------------------------------------------------------------------------
| Quản trị Content Brief — tenant-scoped
| spec/ContentBrief_Technical_Specification.md §4.1 — module thuần quản trị nội bộ, KHÔNG có
| route công khai (khác Page/Menu). middleware 'tenant' được thêm tường minh dù đã global trên
| nhóm 'web', theo đúng convention rõ ràng đã dùng ở các route group tenant-sensitive khác.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant'])->prefix('dashboard/content-briefs')->name('backend.content_brief.')->group(function (): void {
    // ->except(['show']): không có trang xem chi tiết riêng, cùng pattern Menu/Banner/Page.
    Route::resource('items', BriefAdminController::class)->except(['show'])->parameters(['items' => 'brief']);

    Route::get('items/{brief}/versions', [BriefAdminController::class, 'versions'])->name('items.versions');
    Route::post('items/{brief}/submit', [BriefAdminController::class, 'submit'])->name('items.submit');
    Route::post('items/{brief}/approve', [BriefAdminController::class, 'approve'])->name('items.approve');
    Route::post('items/{brief}/reject', [BriefAdminController::class, 'reject'])->name('items.reject');
    Route::post('items/{brief}/restore/{version}', [BriefAdminController::class, 'restore'])->name('items.restore');
    Route::post('items/{brief}/archive', [BriefAdminController::class, 'archive'])->name('items.archive');

    // "Generation" — spec §6. request tạo pending; start/complete/fail có thể được gọi bởi hệ
    // thống sinh nội dung thật (Phase 6, ngoài phạm vi) hoặc thủ công qua UI (§6.0.1).
    Route::post('items/{brief}/generate', [BriefGenerationController::class, 'request'])->name('items.generate');
    Route::post('generations/{generation}/start', [BriefGenerationController::class, 'start'])->name('generations.start');
    Route::post('generations/{generation}/complete', [BriefGenerationController::class, 'complete'])->name('generations.complete');
    Route::post('generations/{generation}/fail', [BriefGenerationController::class, 'fail'])->name('generations.fail');
});
