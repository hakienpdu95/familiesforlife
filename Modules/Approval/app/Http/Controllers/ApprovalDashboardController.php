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

        // content_moderator (Platform Approval Gateway) không thuộc tổ chức nào — thấy pending
        // item của TẤT CẢ tổ chức, không phải chỉ 1 organization_id như user thường.
        $items = $user->isContentModerator()
            ? $service->pendingForModerator($user)
            : $service->pendingFor($user, TenantContext::getOrganizationId());

        $pending = $items->groupBy(fn (ApprovalSubject $s) => $s->subject_type); // group theo loại entity để hiển thị từng khối

        return view('approval::dashboard.index', compact('pending'));
    }
}
