<?php

namespace App\Shared\Tenancy\Models;

use App\Models\User;
use App\Shared\Tenancy\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravelcm\Subscriptions\Traits\HasPlanSubscriptions;
use Modules\Approval\Concerns\HasApproval;

class Organization extends Model
{
    use HasFactory;
    use HasPlanSubscriptions;
    use HasApproval;

    /** Morph alias — ensures consistent subscriber_type regardless of subclass. */
    public function getMorphClass(): string
    {
        return 'organization';
    }

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'status',
        'is_system',
        'owner_id',
        'settings',
        // Marketplace employer registration fields
        'email',
        'website',
        'source',
        'approved_by',
        'approved_at',
        // Phase 0 — Identity enforcement
        'email_domain',
        // AICEM — xem spec/AICEM_Technical_Specification.md mục 5.4, 8.6, 13
        'aicem_content_vertical',
        'ai_provider_config',
        'ai_monthly_budget_usd',
        'ai_rate_limit_override',
    ];

    protected function casts(): array
    {
        return [
            'status'                 => OrganizationStatus::class,
            'is_system'              => 'boolean',
            'settings'               => 'array',
            'ai_provider_config'     => 'encrypted:array',
            'ai_monthly_budget_usd'  => 'float',
            'ai_rate_limit_override' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Organization $org): void {
            if (empty($org->uuid)) {
                $org->uuid = (string) Str::uuid();
            }
            if (empty($org->slug)) {
                $org->slug = static::generateSlug($org->name);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(\Modules\Organization\Models\OrganizationMember::class);
    }

    /**
     * HR admin users của org — dùng để gửi notification offboarding/inactivity.
     * Trả về Collection của User.
     */
    public function hrAdmins(): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('organizationMemberships', function ($q) {
            $q->where('organization_id', $this->id)
              ->whereIn('status', ['active'])
              ->whereIn('role', ['owner', 'admin']);
        })->get();
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OrganizationStatus::Active->value);
    }

    /**
     * Organization là tenant root — không có organization_id trên chính nó,
     * nhưng OrganizationScope vẫn được apply khi TenantContext set (để tránh
     * lọc sai). Scope này dùng cho admin queries cần xem tất cả org.
     */
    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope(\App\Shared\Tenancy\OrganizationScope::class);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /** Org mặc định hệ thống — dùng làm tenant context cho super-admin. */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === OrganizationStatus::Active;
    }

    // ── Platform Approval Gateway (Modules\Approval\Concerns\HasApproval) ─────────────

    /**
     * Organization LÀ tenant root — không có organization_id trỏ tới chính mình như các
     * entity tenant-scoped bình thường (Product…). ApprovalSubject của chính tổ chức này vẫn
     * cần 1 organization_id hợp lệ (FK, NOT NULL) — dùng luôn $this->id.
     */
    public function approvalOrganizationId(): int
    {
        return $this->id;
    }

    /**
     * Trường "nội dung" hồ sơ doanh nghiệp cần Hà Kiên duyệt lại khi thay đổi — KHÔNG gồm
     * status/settings/owner_id/is_system (vận hành nội bộ, không hiển thị công khai) hay
     * approved_by/approved_at (cột cũ chưa từng dùng, không liên quan ApprovalSubject).
     */
    public function approvalWatchedAttributes(): array
    {
        return ['name', 'description', 'industry', 'logo_path', 'website', 'address', 'tax_code'];
    }

    /** Link "Xem & duyệt" cho dashboard Platform Approval Gateway (§12) — trang show (chỉ xem). */
    public function getApprovalDashboardUrlAttribute(): string
    {
        return route('backend.organizations.show', $this);
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    public static function generateSlug(string $name): string
    {
        $base = Str::slug($name);

        // Chỉ match exact slug và các biến thể {base}-{n}, không match {base}-extra
        $existing = static::where('slug', $base)
            ->orWhere('slug', 'like', "{$base}-%")
            ->pluck('slug')
            ->filter(fn (string $s) => $s === $base || preg_match('/^' . preg_quote($base, '/') . '-\d+$/', $s))
            ->all();

        if (empty($existing)) {
            return $base;
        }

        $max = 0;
        foreach ($existing as $s) {
            if ($s === $base) { $max = max($max, 1); continue; }
            $suffix = (int) substr($s, strlen($base) + 1);
            $max    = max($max, $suffix);
        }

        return "{$base}-" . ($max + 1);
    }
}
