<?php

namespace Modules\Sop\Services;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Sop\Models\SopApprovalFlow;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopVersion;
use Modules\Sop\Notifications\SopApprovedNotification;
use Modules\Sop\Notifications\SopNextApproverNotification;
use Modules\Sop\Notifications\SopRejectedNotification;
use Modules\Sop\Notifications\SopSubmittedNotification;

class SopApprovalService
{
    public function __construct(
        private readonly SopVersioningService $versioning,
    ) {}

    /**
     * Gửi SOP để duyệt — tạo SopVersion (submitted) + approval flow records.
     */
    public function submitForReview(SopProcess $sop, User $submitter, ?string $changeSummary = null): SopVersion
    {
        $version = DB::transaction(function () use ($sop, $submitter, $changeSummary) {
            $nextVersionNum = SopVersion::where('sop_id', $sop->id)->max('version_number') + 1;

            $version = SopVersion::create([
                'uuid'           => Str::uuid(),
                'sop_id'         => $sop->id,
                'version_number' => $nextVersionNum,
                'status'         => 'submitted',
                'change_summary' => $changeSummary,
                'created_by'     => $submitter->id,
                'created_at'     => now(),
            ]);

            // Default 1-step approval flow — cấu hình org-level có thể mở rộng sau
            SopApprovalFlow::create([
                'uuid'            => Str::uuid(),
                'sop_version_id'  => $version->id,
                'step_order'      => 1,
                'required_role'   => null, // bất kỳ ai có sop.approve đều có thể duyệt
                'approver_id'     => null,
                'action'          => null,
            ]);

            $sop->update(['status' => 'pending_review']);

            return $version;
        });

        // Notify all approvers in the same org after transaction commits
        $approvers = User::permission(['sop.approve', 'sop.config'])
            ->where('organization_id', $sop->organization_id)
            ->where('id', '!=', $submitter->id)
            ->get();

        if ($approvers->isNotEmpty()) {
            Notification::send($approvers, new SopSubmittedNotification($sop, $version));
        }

        return $version;
    }

    /**
     * Người duyệt chấp thuận một bước approval flow.
     */
    public function approve(SopApprovalFlow $flow, User $approver, ?string $comment = null): void
    {
        $nextPendingFlow = null;
        $versionApproved = false;
        $version         = null;

        DB::transaction(function () use ($flow, $approver, $comment, &$nextPendingFlow, &$versionApproved, &$version) {
            $flow->update([
                'approver_id' => $approver->id,
                'action'      => 'approved',
                'comment'     => $comment,
                'acted_at'    => now(),
            ]);

            $version = $flow->version;

            // Check if there are more pending steps
            $nextPending = SopApprovalFlow::where('sop_version_id', $version->id)
                ->whereNull('action')
                ->orderBy('step_order')
                ->first();

            if ($nextPending) {
                $nextPendingFlow = $nextPending;
                return;
            }

            // All steps approved — create snapshot and finalize
            $this->versioning->createSnapshot($version, $approver->id);
            $versionApproved = true;
        });

        if (!$version) {
            return;
        }

        $sop = $version->sop;

        if ($versionApproved) {
            // Notify creator that the SOP is fully approved
            $creator = $version->createdBy;
            if ($creator && $creator->id !== $approver->id) {
                $creator->notify(new SopApprovedNotification($sop, $version->fresh()));
            }
        } elseif ($nextPendingFlow) {
            // Notify approvers for the next step
            $this->notifyNextApprovers($sop, $version, $nextPendingFlow, $approver);
        }
    }

    /**
     * Người duyệt từ chối phiên bản.
     */
    public function reject(SopApprovalFlow $flow, User $approver, string $comment): void
    {
        $version = null;

        DB::transaction(function () use ($flow, $approver, $comment, &$version) {
            $flow->update([
                'approver_id' => $approver->id,
                'action'      => 'rejected',
                'comment'     => $comment,
                'acted_at'    => now(),
            ]);

            $version = $flow->version;
            $version->update(['status' => 'rejected']);

            // Reset SOP to draft so it can be edited and re-submitted
            SopProcess::where('id', $version->sop_id)->update(['status' => 'draft']);
        });

        if (!$version) {
            return;
        }

        // Notify creator that the SOP was rejected
        $creator = $version->createdBy;
        if ($creator && $creator->id !== $approver->id) {
            $creator->notify(new SopRejectedNotification($version->sop, $version, $comment));
        }
    }

    /**
     * Tìm và gửi thông báo cho approvers của bước tiếp theo.
     */
    private function notifyNextApprovers(
        SopProcess $sop,
        SopVersion $version,
        SopApprovalFlow $nextFlow,
        User $currentApprover,
    ): void {
        $query = User::where('organization_id', $sop->organization_id)
            ->where('id', '!=', $currentApprover->id);

        if ($nextFlow->required_role) {
            // Chỉ notify users có role cụ thể
            $query->role($nextFlow->required_role);
        } else {
            // Notify tất cả users có quyền approve
            $query->permission(['sop.approve', 'sop.config']);
        }

        $approvers = $query->get();

        if ($approvers->isNotEmpty()) {
            Notification::send($approvers, new SopNextApproverNotification($sop, $version, $nextFlow));
        }
    }
}
