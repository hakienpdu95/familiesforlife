<?php

namespace Modules\Organization\Models;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Organization\Enums\ExitReason;
use Modules\Organization\Enums\MemberRole;
use Modules\Organization\Enums\MemberStatus;

/**
 * Pivot/join model for organization membership.
 *
 * Not a TenantAwareModel — the organization_id IS the tenant scope here,
 * so a global scope would be circular.
 */
class OrganizationMember extends Model
{
    protected $table = 'organization_members';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'joined_at',
        // Phase 0 — Exit tracking (§6.2)
        'status',
        'left_at',
        'exit_reason',
        'exit_initiated_by',
        'job_title_at_exit',
        'department_at_exit',
        'role_at_exit',
        'account_was_org_created',
        // Phase 0 — Late offboarding (§5.6)
        'contract_end_date',
        'auto_suspended_at',
        'last_active_at',
        'effective_left_at',
        'offboarded_at',
        'late_offboard_gap_days',
    ];

    protected function casts(): array
    {
        return [
            'role'                   => MemberRole::class,
            'status'                 => MemberStatus::class,
            'exit_reason'            => ExitReason::class,
            'joined_at'              => 'datetime',
            'left_at'                => 'datetime',
            'contract_end_date'      => 'date',
            'auto_suspended_at'      => 'datetime',
            'last_active_at'         => 'datetime',
            'effective_left_at'      => 'datetime',
            'offboarded_at'          => 'datetime',
            'account_was_org_created'=> 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postExitAudit(): HasOne
    {
        return $this->hasOne(MemberPostExitAudit::class, 'org_membership_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === MemberStatus::Active;
    }

    public function isInactive(): bool
    {
        return $this->status === MemberStatus::Inactive;
    }

    public function hasLateOffboardGap(): bool
    {
        return $this->late_offboard_gap_days !== null && $this->late_offboard_gap_days > 0;
    }

    // ── Role aliases (backward compat) ──────────────────────────────
    // Dùng MemberRole enum trực tiếp trong code mới.

    public const ROLE_OWNER   = MemberRole::Owner->value;
    public const ROLE_ADMIN   = MemberRole::Admin->value;
    public const ROLE_MANAGER = MemberRole::Manager->value;
    public const ROLE_MEMBER  = MemberRole::Member->value;

    public static function roles(): array
    {
        return MemberRole::values();
    }
}
