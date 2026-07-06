<?php

namespace Modules\Organization\Models;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPostExitAudit extends Model
{
    protected $table = 'member_post_exit_audits';

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
        'org_membership_id',
        'effective_left_at',
        'offboarded_at',
        'gap_days',
        'login_count_in_gap',
        'sandbox_sessions_in_gap',
        'last_login_in_gap',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_left_at'    => 'datetime',
            'offboarded_at'        => 'datetime',
            'last_login_in_gap'    => 'datetime',
            'reviewed_at'          => 'datetime',
            'created_at'           => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'org_membership_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
