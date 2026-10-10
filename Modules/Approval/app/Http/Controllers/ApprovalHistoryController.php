<?php

namespace Modules\Approval\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Approval\Models\ApprovalLog;

/**
 * Lịch sử duyệt ĐẦY ĐỦ (mọi entity, mọi trạng thái, mọi hành động) — khác
 * ApprovalDashboardController (chỉ hiển thị pending item mà user hiện tại có quyền duyệt).
 * Dành cho vai trò cần giám sát toàn bộ (system_admin/ceo — 1 tổ chức) hoặc content_moderator
 * (Platform Approval Gateway — xuyên MỌI tổ chức), gate bằng permission `approval.view_history`
 * hoặc `isPlatformContentModerator()` (§11 mở rộng). Dữ liệu bảng lấy qua
 * ApprovalApiController::history() (Tabulator, remote pagination).
 */
class ApprovalHistoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewApprovalHistory');

        return view('approval::history.index', [
            'subjectTypes' => collect(config('approval.subjects'))->map(fn (array $config) => $config['label']),
            'actions' => ApprovalLog::ACTION_LABELS,
        ]);
    }
}
