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

class Organization extends Model
{
    use HasFactory;
    use HasPlanSubscriptions;

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
    ];

    protected function casts(): array
    {
        return [
            'status'    => OrganizationStatus::class,
            'is_system' => 'boolean',
            'settings'  => 'array',
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
