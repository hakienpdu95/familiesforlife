<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Sop\Models\SopApprovalFlow;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopVersion;
use Modules\Sop\Services\SopVersioningService;

class SopVersionController extends Controller
{
    public function __construct(
        private readonly SopVersioningService $versioning,
    ) {}

    /**
     * GET /dashboard/sop/{sop}/versions/{version}/review
     * Trang xem snapshot + form approve/reject.
     */
    public function review(SopProcess $sop, SopVersion $version)
    {
        $this->authorize('view', $sop);

        abort_unless($version->sop_id === $sop->id, 404);

        $version->load([
            'createdBy:id,name',
            'approvedBy:id,name',
            'approvalFlows.approver:id,name',
        ]);

        $sop->load(['owner:id,name']);

        $pendingFlow = $version->approvalFlows->first(fn ($f) => $f->isPending());

        $canApprove = $pendingFlow && auth()->user()->can('approve', $sop);

        return view('sop::version-review', compact('sop', 'version', 'pendingFlow', 'canApprove'));
    }

    /**
     * GET /backend/api/sop/{sop}/versions/{version}/flowchart-data
     * JSON endpoint — snapshot flowchart cho Alpine.js render.
     */
    public function flowchartData(SopProcess $sop, SopVersion $version): JsonResponse
    {
        $this->authorize('view', $sop);

        abort_unless($version->sop_id === $sop->id, 404);

        $data = $this->versioning->getVersionFlowchartData($version);

        return response()->json($data);
    }

    /**
     * POST /backend/api/sop/{sop}/rollback/{version}
     * Rollback live-layer về snapshot version (chỉ System_Admin / owner).
     */
    public function rollback(SopProcess $sop, SopVersion $version): JsonResponse
    {
        $this->authorize('update', $sop);

        abort_unless($version->sop_id === $sop->id, 404);
        abort_unless($version->status?->value === 'approved', 422, 'Chỉ có thể rollback về version đã approved.');

        $this->versioning->rollbackToVersion($sop->id, $version->version_number);

        return response()->json(['message' => "Đã rollback về phiên bản v{$version->version_number}."]);
    }
}
