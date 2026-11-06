<?php

namespace App\Models;

use App\Enums\AccountType;
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
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password',
    'department', 'is_active', 'last_active_at',
    // Phase 0 — Identity Foundation
    'account_type', 'trust_level',
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

    /** Category mà platform_section_editor này được gán phụ trách (spec/Platform_RBAC_Phase2_Specification.md §4.2). */
    public function postCategoryEditorships(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\Modules\Post\Models\PostCategory::class, 'post_category_editors', 'user_id', 'post_category_id');
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

    /**
     * spec/Author_Contributor_Hub_Technical_Specification.md §7.4 — quan hệ ĐỌC duy nhất cần
     * thêm vào User cho Author Hub (không phải cột, không phá vỡ nguyên tắc "không thêm cột
     * vào users" — §0). Hồ sơ tác giả công khai sống ở Modules/Post (post_author_profiles).
     */
    public function authorProfile(): HasOne
    {
        return $this->hasOne(\Modules\Post\Models\PostAuthorProfile::class);
    }

    // ── Identity helpers ─────────────────────────────────────────────

    public function isFree(): bool
    {
        return $this->account_type === AccountType::Free;
    }

    public function isPlatform(): bool
    {
        return $this->account_type === AccountType::Platform;
    }

    public function isSuspended(): bool
    {
        return $this->account_type === AccountType::Suspended;
    }

    public function hasGlobalRole(string $role): bool
    {
        return $this->hasRole($role);
    }

    /**
     * @param  array<int, string>  $roleNames
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function withGlobalRole(array $roleNames): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereHas('roles', fn ($q) => $q->whereIn('name', $roleNames))->get();
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
     * Vận hành Platform — hỗ trợ kỹ thuật, xem log hệ thống.
     * KHÔNG có ability nào trên approve/reject/publishApproval/archiveApproval của
     * Product/Post (spec/Platform_RBAC_Technical_Specification.md §3.3).
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
