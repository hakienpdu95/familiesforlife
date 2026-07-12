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

    /** Đội kiểm duyệt Doanh nghiệp/Sản phẩm (Platform Approval Gateway, §18). */
    public function isContentModerator(): bool
    {
        return $this->hasGlobalRole('content_moderator');
    }

    /** Biên tập viên — duyệt SƠ BỘ bài viết (Submitted → Approved), §18.10. */
    public function isContentEditor(): bool
    {
        return $this->hasGlobalRole('content_editor');
    }

    /** Trưởng phòng nội dung — duyệt CUỐI CÙNG + xuất bản bài viết (Approved → Published), §18.10. */
    public function isContentHead(): bool
    {
        return $this->hasGlobalRole('content_head');
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
