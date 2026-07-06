<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Sop\Models\SopApprovalFlow;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopVersion;
use Modules\Sop\Services\SopApprovalService;
use App\Shared\Tenancy\TenantContext;

class SopApprovalController extends Controller
{
    public function __construct(
        private readonly SopApprovalService $approval,
    ) {}

    /**
     * GET /dashboard/sop/pending-approvals
     * Danh sách phiên bản SOP đang chờ duyệt của user hiện tại.
     */
    public function pendingList()
    {
        $this->authorize('viewAny', SopProcess::class);

        $user  = auth()->user();
        $orgId = TenantContext::getOrganizationId();

        // Lấy tất cả approval flow đang pending trong org của user
        $pendingFlows = SopApprovalFlow::whereNull('action')
            ->whereHas('version.sop', fn ($q) => $q->where('organization_id', $orgId))
            ->with([
                'version:id,uuid,sop_id,version_number,status,change_summary,created_at,created_by',
                'version.createdBy:id,name',
                'version.sop:id,uuid,code,title,status',
            ])
            ->orderBy('created_at')
            ->get()
            ->filter(fn ($f) => $user->can('approve', $f->version->sop));

        return view('sop::pending-approvals', compact('pendingFlows'));
    }

    /**
     * POST /backend/api/sop/{sop}/submit-review
     * Gửi SOP để duyệt.
     */
    public function submitReview(Request $request, SopProcess $sop): JsonResponse
    {
        $this->authorize('submitReview', $sop);

        abort_unless(
            in_array($sop->status?->value, ['draft', 'rejected']),
            422,
            'Chỉ có thể gửi duyệt khi SOP đang ở trạng thái draft hoặc rejected.'
        );

        $changeSummary = $request->string('change_summary')->trim()->value();

        $version = $this->approval->submitForReview(
            sop: $sop,
            submitter: auth()->user(),
            changeSummary: $changeSummary ?: null,
        );

        return response()->json([
            'message'        => 'Đã gửi phiên bản v' . $version->version_number . ' để duyệt.',
            'version_number' => $version->version_number,
            'version_uuid'   => $version->uuid,
        ]);
    }

    /**
     * POST /backend/api/sop/approval-flows/{flow}/approve
     */
    public function approve(Request $request, SopApprovalFlow $flow): JsonResponse
    {
        $this->authorize('approve', $flow->version->sop);

        abort_unless($flow->isPending(), 422, 'Bước duyệt này đã được xử lý.');

        $this->approval->approve(
            flow: $flow,
            approver: auth()->user(),
            comment: $request->string('comment')->trim()->value() ?: null,
        );

        $version = $flow->fresh('version')->version;
        $finalStatus = $version->status?->value;

        return response()->json([
            'message' => $finalStatus === 'approved'
                ? "SOP đã được duyệt và chốt phiên bản v{$version->version_number}."
                : 'Đã duyệt bước này, chờ duyệt tiếp theo.',
            'sop_status'     => $version->sop?->status?->value,
            'version_status' => $finalStatus,
        ]);
    }

    /**
     * POST /backend/api/sop/approval-flows/{flow}/reject
     */
    public function reject(Request $request, SopApprovalFlow $flow): JsonResponse
    {
        $this->authorize('approve', $flow->version->sop);

        abort_unless($flow->isPending(), 422, 'Bước duyệt này đã được xử lý.');

        $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $this->approval->reject(
            flow: $flow,
            approver: auth()->user(),
            comment: $request->string('comment')->trim()->value(),
        );

        return response()->json([
            'message' => 'Đã từ chối phiên bản. SOP được đưa về trạng thái draft.',
        ]);
    }
}
