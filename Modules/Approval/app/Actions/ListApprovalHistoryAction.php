<?php

namespace Modules\Approval\Actions;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Models\ApprovalLog;

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
        return ApprovalLog::query()
            ->with(['performedBy:id,name,email', 'subject.subject'])
            ->when($subjectType, fn ($q) => $q->whereHas('subject', fn ($s) => $s->where('subject_type', $subjectType)))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->orderBy('id', $sortDir)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
