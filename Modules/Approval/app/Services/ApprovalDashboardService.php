<?php

namespace Modules\Approval\Services;

use App\Models\User;
use App\Shared\Tenancy\OrganizationScope;
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
    public function pendingFor(User $user, int $organizationId): Collection
    {
        return collect(config('approval.subjects'))
            ->flatMap(function (array $config, string $type) use ($user, $organizationId) {
                return ApprovalSubject::query()
                    ->where('organization_id', $organizationId)
                    ->where('subject_type', (new $config['model'])->getMorphClass())
                    ->where('status', ApprovalStatus::Pending)
                    ->with('subject')
                    ->get()
                    ->filter(function (ApprovalSubject $s) use ($user) {
                        // Gate::forUser($user)->allows(...) thay vì $user->can(...) — tường
                        // minh rằng đây là 1 lần authorization độc lập cho TỪNG item, không
                        // liên quan tới user hiện tại đang đăng nhập trong request gốc (dùng
                        // được cả khi $user truyền vào khác auth()->user(), vd job/console).
                        // Ability 'approve' phải tồn tại trên Policy của từng subject_type
                        // (§9.3) — nếu Policy chưa có method này, Gate trả false an toàn.
                        return $s->subject && Gate::forUser($user)->allows('approve', $s->subject);
                    });
            })
            ->groupBy(fn (ApprovalSubject $s) => $s->subject_type)
            ->flatten(1);
    }

    /**
     * Platform Approval Gateway (hệ thống nội bộ Hà Kiên) — dùng cho MỌI tài khoản Platform
     * (organization_id=null: content_moderator, super-admin, platform_viewer...), thấy pending
     * item của TẤT CẢ tổ chức, không giới hạn 1 organization_id như pendingFor() ở trên.
     *
     * KHÔNG dùng `with('subject')` (eager-load morphTo mặc định) — Laravel tự query RIÊNG
     * theo từng model type bên trong morphTo, và query đó VẪN áp OrganizationScope của chính
     * model đó (Product/Organization), khiến toàn bộ kết quả rỗng với content_moderator
     * (không có TenantContext khớp bất kỳ tổ chức nào). Phải tự fetch entity thủ công với
     * `withoutGlobalScope()` tường minh rồi `setRelation()` lại, thay vì dựa vào eager-load
     * tự động — bug thật phát hiện khi build tính năng này.
     */
    public function pendingForModerator(User $user): Collection
    {
        if ($user->organization_id !== null) {
            return collect();
        }

        return collect(config('approval.subjects'))
            ->flatMap(function (array $config) use ($user) {
                $modelClass = $config['model'];
                $morphAlias = (new $modelClass)->getMorphClass();

                $subjects = ApprovalSubject::query()
                    ->withoutGlobalScope(OrganizationScope::class)
                    ->where('subject_type', $morphAlias)
                    ->where('status', ApprovalStatus::Pending)
                    ->get();

                if ($subjects->isEmpty()) {
                    return collect();
                }

                // Eager-load 'organization' nếu entity có quan hệ đó (vd Product) — để Blade
                // hiển thị tên tổ chức sở hữu (moderator quản lý nhiều tổ chức cùng lúc, cần
                // phân biệt). Entity chính nó LÀ 1 organization (vd Organization) thì không có
                // quan hệ này. KHÔNG bỏ qua bước eager-load này — nếu Blade lazy-load
                // $entity->organization cho ≥ 2 item cùng lúc sẽ dính lại đúng
                // LazyLoadingViolationException đã gặp ở §17.10.
                $entityQuery = $modelClass::withoutGlobalScope(OrganizationScope::class)
                    ->whereIn('id', $subjects->pluck('subject_id'));

                if (method_exists($modelClass, 'organization')) {
                    $entityQuery->with('organization');
                }

                $entities = $entityQuery->get()->keyBy('id');

                return $subjects
                    ->each(fn (ApprovalSubject $s) => $s->setRelation('subject', $entities->get($s->subject_id)))
                    ->filter(fn (ApprovalSubject $s) => $s->subject && (
                        // platform_viewer (Lớp A, read-only — §3.3) thấy TOÀN BỘ hàng đợi để
                        // giám sát, không lọc theo ability 'approve' như content_moderator/
                        // super-admin — vì họ vốn không có (và không nên có) quyền approve nào,
                        // lọc theo ability sẽ khiến dashboard của họ luôn trống rỗng vô nghĩa.
                        // Nút Duyệt/Từ chối vẫn ẩn đúng ở Blade (gate riêng theo @can('approve')).
                        $user->isPlatformViewer() || Gate::forUser($user)->allows('approve', $s->subject)
                    ));
            })
            ->groupBy(fn (ApprovalSubject $s) => $s->subject_type)
            ->flatten(1);
    }
}
