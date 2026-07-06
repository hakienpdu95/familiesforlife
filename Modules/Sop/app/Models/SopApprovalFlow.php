<?php

namespace Modules\Sop\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sop\Enums\ApprovalAction;

class SopApprovalFlow extends Model
{
    protected $table = 'sop_approval_flows';

    protected $fillable = [
        'uuid',
        'sop_version_id',
        'step_order',
        'required_role',
        'approver_id',
        'action',
        'comment',
        'acted_at',
    ];

    protected $casts = [
        'action'   => ApprovalAction::class,
        'acted_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function version(): BelongsTo
    {
        return $this->belongsTo(SopVersion::class, 'sop_version_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->action === null;
    }

    public function isApproved(): bool
    {
        return $this->action === ApprovalAction::Approved;
    }

    public function isRejected(): bool
    {
        return $this->action === ApprovalAction::Rejected;
    }
}
