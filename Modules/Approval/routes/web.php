<?php

use Illuminate\Support\Facades\Route;
use Modules\Approval\Http\Controllers\ApprovalDashboardController;
use Modules\Approval\Http\Controllers\ApprovalHistoryController;

// spec/Workflow_Approval_Technical_Specification.md §12, §13 — Approval chỉ sở hữu route
// dashboard xuyên-entity + lịch sử duyệt; toàn bộ route transition (submit-approval,
// approve-content…) thuộc về module tiêu thụ (vd Modules/Product/routes/web.php).
Route::middleware(['auth', 'tenant'])->group(function (): void {
    Route::get('dashboard/approvals', [ApprovalDashboardController::class, 'index'])
        ->name('backend.approval.dashboard');

    // Lịch sử duyệt đầy đủ (mọi entity/trạng thái) — dành cho system_admin/ceo giám sát,
    // khác dashboard ở trên (chỉ hiển thị pending item user hiện tại có quyền duyệt).
    Route::get('dashboard/approvals/history', [ApprovalHistoryController::class, 'index'])
        ->name('backend.approval.history');
});
