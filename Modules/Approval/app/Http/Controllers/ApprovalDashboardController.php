<?php

namespace Modules\Approval\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Approval\Models\ApprovalSubject;
use Modules\Approval\Services\ApprovalDashboardService;

/**
 * spec/Workflow_Approval_Technical_Specification.md §12.
 */
class ApprovalDashboardController extends Controller
{
    public function index(Request $request, ApprovalDashboardService $service): View
    {
        $this->authorize('viewDashboard'); // Gate::define riêng — permission approval.view_dashboard HOẶC content_moderator

        $user = $request->user();

        // BẤT KỲ tài khoản Platform nào (organization_id=null — content_moderator, super-admin,
        // và giờ có thêm platform_viewer) đều không thuộc tổ chức nào — thấy pending item của
        // TẤT CẢ tổ chức, không phải chỉ 1 organization_id như user thường. Bug thật phát hiện
        // khi thêm platform_viewer: điều kiện cũ chỉ check isContentModerator() nên mọi role
        // organization_id=null KHÁC (kể cả super-admin, vốn bypass Gate::before) rơi vào nhánh
        // pendingFor(TenantContext::getOrganizationId()) — trả về null, ném TypeError vì
        // pendingFor() khai báo tham số int không nullable.
        $items = $user->organization_id === null
            ? $service->pendingForModerator($user)
            : $service->pendingFor($user, TenantContext::getOrganizationId());

        $pending = $items->groupBy(fn (ApprovalSubject $s) => $s->subject_type); // group theo loại entity để hiển thị từng khối

        return view('approval::dashboard.index', compact('pending'));
    }
}
