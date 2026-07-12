<?php

namespace Modules\Approval\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\OrganizationScope;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Approval\Models\ApprovalLog;

/**
 * Lịch sử duyệt ĐẦY ĐỦ (mọi entity, mọi trạng thái, mọi hành động) — khác
 * ApprovalDashboardController (chỉ hiển thị pending item mà user hiện tại có quyền duyệt).
 * Dành cho vai trò cần giám sát toàn bộ (system_admin/ceo — 1 tổ chức) hoặc content_moderator
 * (Platform Approval Gateway — xuyên MỌI tổ chức), gate bằng permission `approval.view_history`
 * hoặc `isContentModerator()` (§11 mở rộng).
 */
class ApprovalHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewApprovalHistory');

        $subjectTypeFilter = $request->string('subject_type')->value() ?: null;
        $actionFilter = $request->string('action')->value() ?: null;
        $isModerator = $request->user()->isContentModerator();

        $query = ApprovalLog::query()
            ->when(
                $isModerator,
                // content_moderator: bỏ scope tổ chức — xem lịch sử của MỌI tổ chức.
                fn ($q) => $q->withoutGlobalScope(OrganizationScope::class),
                // user thường: chỉ tổ chức hiện tại (TenantContext) — hành vi cũ, không đổi.
                fn ($q) => $q->where('organization_id', TenantContext::getOrganizationId()),
            )
            ->with(['performedBy:id,name,email'])
            ->when($subjectTypeFilter, fn ($q) => $q->whereHas('subject', fn ($s) => $s->withoutGlobalScope(OrganizationScope::class)->where('subject_type', $subjectTypeFilter)))
            ->when($actionFilter, fn ($q) => $q->where('action', $actionFilter))
            ->latest('id');

        $logs = $query->paginate(30)->withQueryString();

        // subject.subject (ApprovalSubject → entity thật) load thủ công thay vì eager-load tự
        // động — cùng lý do đã ghi ở ApprovalDashboardService::pendingForModerator(): morphTo
        // tự query riêng theo từng model type và áp OrganizationScope của chính model đó,
        // làm rỗng kết quả với content_moderator.
        $subjects = \Modules\Approval\Models\ApprovalSubject::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->whereIn('id', $logs->pluck('approval_subject_id'))
            ->get()
            ->keyBy('id');

        foreach (config('approval.subjects', []) as $cfg) {
            $modelClass = $cfg['model'];
            $ids = $subjects->filter(fn ($s) => $s->subject_type === (new $modelClass)->getMorphClass())->pluck('subject_id');
            if ($ids->isEmpty()) {
                continue;
            }
            $entities = $modelClass::withoutGlobalScope(OrganizationScope::class)->whereIn('id', $ids)->get()->keyBy('id');
            $subjects->each(function ($s) use ($modelClass, $entities) {
                if ($s->subject_type === (new $modelClass)->getMorphClass()) {
                    $s->setRelation('subject', $entities->get($s->subject_id));
                }
            });
        }

        $logs->each(fn (ApprovalLog $log) => $log->setRelation('subject', $subjects->get($log->approval_subject_id)));

        return view('approval::history.index', [
            'logs'              => $logs,
            'subjectTypeFilter' => $subjectTypeFilter,
            'actionFilter'      => $actionFilter,
        ]);
    }
}
