<?php

namespace Modules\RoleScope\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Branch\Models\Branch;
use Modules\Department\Models\Department;
use Spatie\Permission\Models\Role;

class UserRoleScope extends TenantAwareModel
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
        'role_id',
        'scope_branch_id',
        'scope_dept_id',
        'granted_by',
        'granted_at',
        'expires_at',
        'note',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function scopeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'scope_branch_id');
    }

    public function scopeDept(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'scope_dept_id');
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getScopeLevelAttribute(): string
    {
        if ($this->scope_branch_id === null && $this->scope_dept_id === null) {
            return 'org';
        }

        if ($this->scope_branch_id !== null && $this->scope_dept_id === null) {
            return 'branch';
        }

        return 'dept';
    }

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope_level) {
            'org'    => 'Toàn tổ chức',
            'branch' => 'Chi nhánh: ' . ($this->scopeBranch?->name ?? '—'),
            'dept'   => 'Phòng ban: ' . ($this->scopeDept?->name ?? '—'),
            default  => '—',
        };
    }
}
