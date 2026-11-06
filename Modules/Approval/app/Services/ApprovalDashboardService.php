<?php

namespace Modules\Approval\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

/**
 * spec/Workflow_Approval_Technical_Specification.md §12.
 */
class ApprovalDashboardService
{
    /**
     * @return Collection<int, ApprovalSubject> mỗi phần tử đã eager-load `subject`, nhóm sẵn
     *         theo `subject_type` để Controller/Blade group hiển thị (dùng `label` ở config).
     */
    public function pendingForUser(User $user): Collection
    {
        return collect(config('approval.subjects'))
            ->flatMap(function (array $config) use ($user) {
                return ApprovalSubject::query()
                    ->where('subject_type', (new $config['model'])->getMorphClass())
                    ->where('status', ApprovalStatus::Pending)
                    ->with('subject')
                    ->get()
                    ->filter(fn (ApprovalSubject $s) => $s->subject && (
                        // platform_viewer (read-only — §3.3) thấy TOÀN BỘ hàng đợi để giám sát;
                        // nút Duyệt/Từ chối vẫn ẩn đúng ở Blade (gate riêng theo @can('approve')).
                        $user->isPlatformViewer() || Gate::forUser($user)->allows('approve', $s->subject)
                    ));
            })
            ->groupBy(fn (ApprovalSubject $s) => $s->subject_type)
            ->flatten(1);
    }
}
