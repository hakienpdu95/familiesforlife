<?php

namespace Modules\Approval\Actions;

use App\Models\User;
use App\Shared\Tenancy\OrganizationScope;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Models\ApprovalLog;
use Modules\Approval\Models\ApprovalSubject;

class ListApprovalHistoryAction
{
    use AsAction;

    public function handle(
        User $user,
        ?string $subjectType = null,
        ?string $action = null,
        string $sortDir = 'desc',
        int $perPage = 25,
        int $page = 1,
    ): LengthAwarePaginator {
        // content_moderator: bỏ scope tổ chức — xem lịch sử của MỌI tổ chức; user thường: chỉ tổ chức hiện tại.
        $logs = ApprovalLog::query()
            ->when(
                $user->isPlatformContentModerator(),
                fn ($q) => $q->withoutGlobalScope(OrganizationScope::class),
                fn ($q) => $q->where('organization_id', TenantContext::getOrganizationId()),
            )
            ->with(['performedBy:id,name,email'])
            ->when($subjectType, fn ($q) => $q->whereHas('subject', fn ($s) => $s->withoutGlobalScope(OrganizationScope::class)->where('subject_type', $subjectType)))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->orderBy('id', $sortDir)
            ->paginate($perPage, ['*'], 'page', $page);

        // subject.subject load thủ công thay vì eager-load: morphTo tự query riêng theo từng
        // model type và áp OrganizationScope của chính model đó, làm rỗng kết quả với
        // content_moderator — xem ApprovalDashboardService::pendingForModerator().
        $subjects = ApprovalSubject::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->whereIn('id', $logs->pluck('approval_subject_id'))
            ->get()
            ->keyBy('id');

        foreach (config('approval.subjects', []) as $cfg) {
            $modelClass = $cfg['model'];
            $morph = (new $modelClass)->getMorphClass();
            $ids = $subjects->where('subject_type', $morph)->pluck('subject_id');
            if ($ids->isEmpty()) {
                continue;
            }
            $entities = $modelClass::withoutGlobalScope(OrganizationScope::class)->whereIn('id', $ids)->get()->keyBy('id');
            $subjects->where('subject_type', $morph)->each(fn ($s) => $s->setRelation('subject', $entities->get($s->subject_id)));
        }

        $logs->each(fn (ApprovalLog $log) => $log->setRelation('subject', $subjects->get($log->approval_subject_id)));

        return $logs;
    }
}
