<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Auth\Models\SocialAccount;
use Modules\Organization\Models\OrganizationMember;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password',
    'organization_id', 'department', 'is_active', 'last_active_at',
    // Phase 0 — Identity Foundation
    'account_type', 'current_org_id', 'trust_level',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'last_active_at'     => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'account_type'       => AccountType::class,
            'trust_level'        => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Category mà platform_section_editor này được gán phụ trách (spec/Platform_RBAC_Phase2_Specification.md §4.2). */
    public function postCategoryEditorships(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\Modules\Post\Models\PostCategory::class, 'post_category_editors', 'user_id', 'post_category_id');
    }

    public function organizationMembership(): HasOne
    {
        return $this->hasOne(OrganizationMember::class);
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    // ── Identity helpers ─────────────────────────────────────────────

    public function isFree(): bool
    {
        return $this->account_type === AccountType::Free;
    }

    public function isOrgMember(): bool
    {
        return $this->account_type === AccountType::OrgMember;
    }

    public function isPlatform(): bool
    {
        return $this->account_type === AccountType::Platform;
    }

    public function isSuspended(): bool
    {
        return $this->account_type === AccountType::Suspended;
    }

    // ── Tenant Helpers ───────────────────────────────────────────────

    /**
     * Returns the organization ID for the current request context.
     * Used by policies for same-tenant checks.
     *
     * Priority: TenantContext (middleware-resolved) → user's own organization_id.
     */
    public function getCurrentOrganizationIdAttribute(): ?int
    {
        return TenantContext::getOrganizationId() ?? $this->organization_id;
    }

    public function getCurrentOrganizationAttribute(): ?Organization
    {
        return TenantContext::get() ?? $this->organization;
    }

    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organization_id === $organizationId;
    }

    /**
     * Kiểm tra 1 role "xuyên tổ chức" (Platform Approval Gateway —
     * spec/Workflow_Approval_Technical_Specification.md §18) — organization_id = null, giống
     * quy ước super-admin. Dùng cho content_moderator (duyệt Doanh nghiệp/Sản phẩm),
     * content_editor/content_head (2 cấp duyệt bài viết, §18.10).
     *
     * CỐ Ý KHÔNG dùng `hasRole()` (Spatie team-scoped) — verify thật cho thấy hasRole() chỉ
     * trả đúng khi ambient Spatie team (getPermissionsTeamId()) CŨNG đang null tại thời điểm
     * gọi; vì các tài khoản này thao tác trên dữ liệu của NHIỀU tổ chức khác nhau trong cùng 1
     * request (mỗi request cần set TenantContext sang đúng tổ chức đang xử lý để
     * OrganizationScope không chặn), ambient team context không ổn định qua từng bước xử lý.
     * Query thẳng bảng pivot — không phụ thuộc getPermissionsTeamId() — để kết quả luôn đúng
     * bất kể đang "đứng" ở tổ chức nào.
     */
    public function hasGlobalRole(string $role): bool
    {
        if ($this->organization_id !== null) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table(config('permission.table_names.model_has_roles'))
            ->join('roles', 'roles.id', '=', config('permission.table_names.model_has_roles') . '.role_id')
            ->where(config('permission.table_names.model_has_roles') . '.model_id', $this->id)
            ->where(config('permission.table_names.model_has_roles') . '.model_type', static::class)
            ->where('roles.name', $role)
            ->exists();
    }

    /**
     * Lấy TẤT CẢ user đang giữ ≥1 trong các role Platform (organization_id=null) truyền vào —
     * dùng cho các nơi cần tìm danh sách nhân sự nền tảng để gửi thông báo (vd
     * ExpireSponsoredArticlesJob, TakeDownArticleTranslationAction —
     * spec/Platform_RBAC_Phase2_Specification.md §3.3). CỐ Ý không dùng `User::role([...])`
     * (Spatie team-scoped `whereHas('roles', ...)`) — cùng lý do đã tài liệu ở
     * `hasGlobalRole()`: kết quả phụ thuộc ambient `getPermissionsTeamId()` tại thời điểm gọi,
     * không ổn định trong queue worker chạy nhiều job liên tiếp. Query thẳng pivot để luôn đúng.
     *
     * @param  array<int, string>  $roleNames
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function withGlobalRole(array $roleNames): \Illuminate\Support\Collection
    {
        $userIds = \Illuminate\Support\Facades\DB::table(config('permission.table_names.model_has_roles'))
            ->join('roles', 'roles.id', '=', config('permission.table_names.model_has_roles') . '.role_id')
            ->where(config('permission.table_names.model_has_roles') . '.model_type', static::class)
            ->whereIn('roles.name', $roleNames)
            ->pluck(config('permission.table_names.model_has_roles') . '.model_id');

        return static::whereNull('organization_id')->whereIn('id', $userIds)->get();
    }

    /**
     * Đội kiểm duyệt Doanh nghiệp/Sản phẩm (Platform Approval Gateway, §18). Đổi tên từ
     * isContentModerator()/role content_moderator — spec/Platform_RBAC_Technical_Specification.md
     * §0/§3.2 (tiền tố platform_ nhất quán với platform_ops/platform_viewer).
     */
    public function isPlatformContentModerator(): bool
    {
        return $this->hasGlobalRole('platform_content_moderator');
    }

    /**
     * Biên tập viên — duyệt SƠ BỘ bài viết (Submitted → Approved), §18.10. Đổi tên từ
     * isContentEditor()/role content_editor — spec/Platform_RBAC_Technical_Specification.md §3.2.
     */
    public function isPlatformContentEditor(): bool
    {
        return $this->hasGlobalRole('platform_content_editor');
    }

    /**
     * Viết/sửa bài + upload media (spec/Platform_RBAC_Phase2_Specification.md §3.1, v3.0) —
     * gộp 2 role dự kiến ban đầu (`platform_reporter`/`platform_media`) thành 1. KHÔNG có
     * quyền publish/duyệt — tách biệt viết vs duyệt để tránh tự duyệt bài của chính mình.
     */
    public function isPlatformContentCreator(): bool
    {
        return $this->hasGlobalRole('platform_content_creator');
    }

    /**
     * Trưởng phòng nội dung — duyệt CUỐI CÙNG + xuất bản bài viết (Approved → Published),
     * §18.10. Đổi tên từ isContentHead()/role content_head —
     * spec/Platform_RBAC_Technical_Specification.md §3.2.
     */
    public function isPlatformContentHead(): bool
    {
        return $this->hasGlobalRole('platform_content_head');
    }

    /**
     * Vận hành Platform — quản lý subscription tổ chức, hỗ trợ kỹ thuật, xem log hệ thống.
     * KHÔNG có ability nào trên approve/reject/publishApproval/archiveApproval của
     * Organization/Product/Post (spec/Platform_RBAC_Technical_Specification.md §3.3).
     */
    /**
     * Biên tập viên trưởng chuyên mục — duyệt sơ bộ (Submitted → Approved) NHƯNG chỉ giới hạn
     * trong các category được gán qua `post_category_editors` (spec/Platform_RBAC_Phase2_Specification.md
     * §4, v3.0) — xem `PostArticlePolicy::approve()`.
     */
    public function isPlatformSectionEditor(): bool
    {
        return $this->hasGlobalRole('platform_section_editor');
    }

    public function isPlatformOps(): bool
    {
        return $this->hasGlobalRole('platform_ops');
    }

    /**
     * Giám sát / Viewer — role read-only đầu tiên ở Lớp A, chỉ xem dashboard/báo cáo, không
     * có ability ghi nào (spec/Platform_RBAC_Technical_Specification.md §3.3).
     */
    public function isPlatformViewer(): bool
    {
        return $this->hasGlobalRole('platform_viewer');
    }

    /**
     * Tên hiển thị tiếng Việt cho 6 Platform Role (Lớp A) —
     * spec/Platform_RBAC_Technical_Specification.md §3.1. Chưa có màn hình quản trị nào hiển
     * thị role Platform ra UI (xem §3.9 — chỉ có CLI `platform:user-create`), nên map này dùng
     * ngay cho output CLI hiện tại và sẵn sàng tái sử dụng khi có màn hình "Quản lý nhân sự
     * Platform" (Phase sau).
     */
    public static function platformRoleLabels(): array
    {
        return [
            'super-admin'                => 'Super Admin',
            'platform_content_head'      => 'Tổng biên tập',
            'platform_content_editor'    => 'Biên tập viên',
            'platform_content_creator'   => 'Phóng viên / Biên tập viết bài',
            'platform_section_editor'    => 'Biên tập viên trưởng chuyên mục',
            'platform_content_moderator' => 'Kiểm duyệt viên (Legal)',
            'platform_ops'               => 'Vận hành Platform',
            'platform_viewer'            => 'Giám sát / Viewer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'department', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => "Tạo tài khoản: {$this->email}",
                'updated' => "Cập nhật tài khoản: {$this->email}",
                'deleted' => "Xóa tài khoản: {$this->email}",
                default   => $event,
            })
            ->useLogName('Auth');
    }
}
