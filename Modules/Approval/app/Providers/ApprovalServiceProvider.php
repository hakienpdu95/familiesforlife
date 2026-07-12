<?php

namespace Modules\Approval\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Modules\Approval\Console\Commands\BackfillApprovalSubjectsCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ApprovalServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Approval';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'approval';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        BackfillApprovalSubjectsCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Build morph map từ config/approval.php thay vì hard-code danh sách entity ở 2 nơi
        // (spec/Workflow_Approval_Technical_Specification.md §5). Rỗng ở Phase 1 (chưa có
        // entity nào tích hợp — Product tích hợp ở Phase 4).
        //
        // CỐ Ý dùng morphMap() (merge: true), KHÔNG dùng enforceMorphMap()/requireMorphMap():
        // enforceMorphMap() bật cờ TOÀN CỤC "mọi model dùng trong bất kỳ quan hệ polymorphic
        // nào trong app đều PHẢI có trong morph map, nếu không getMorphClass() ném
        // ClassMorphViolationException" — áp dụng cho MỌI polymorphic relation của toàn bộ
        // ứng dụng (Spatie Activitylog subject/causer trên TenantAwareModel, Spatie Permission
        // model_has_roles, Laravel notifications notifiable_type…), không chỉ riêng
        // ApprovalSubject.subject. Một module con không có thẩm quyền bật cờ đó cho cả app.
        // morphMap() chỉ đăng ký alias đẹp cho các model khai báo ở đây, không ảnh hưởng model
        // khác (chúng vẫn fallback về FQCN thô như trước).
        Relation::morphMap(
            collect(config('approval.subjects', []))->map(fn (array $subject) => $subject['model'])->all(),
            merge: true,
        );

        // Gate riêng cho dashboard xuyên-entity (§11, §12) — không gắn Policy vì không có
        // model cụ thể nào để resolve (khác các ability submitForApproval/approve/… gate trên
        // từng entity ở Modules/Product). content_moderator (Platform Approval Gateway) LUÔN
        // được xem dashboard — dùng isContentModerator() (không team-scoped) thay vì
        // $user->can(...) (Spatie permission team-scoped, không đáng tin cho tài khoản
        // organization_id=null — xem User::isContentModerator()).
        Gate::define('viewDashboard', fn (User $user) => $user->can('approval.view_dashboard') || $user->isContentModerator());

        // Gate riêng cho trang Lịch sử duyệt đầy đủ (§11 mở rộng) — rộng hơn viewDashboard
        // (thấy MỌI log, không chỉ pending item user tự duyệt được), dành cho vai trò giám sát.
        Gate::define('viewApprovalHistory', fn (User $user) => $user->can('approval.view_history') || $user->isContentModerator());
    }
}
