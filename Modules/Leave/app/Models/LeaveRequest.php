<?php

namespace Modules\Leave\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Employee\Models\Employee;
use Modules\Leave\Enums\LeaveRequestStatus;
use Modules\Leave\Enums\LeaveType;
use Modules\Leave\Observers\LeaveRequestObserver;

class LeaveRequest extends TenantAwareModel
{
    protected $table = 'leave_requests';

    protected $fillable = [
        'uuid',
        'organization_id',
        'employee_id',
        'balance_id',
        'leave_type',
        'date_from',
        'date_to',
        'days_count',
        'status',
        'reason',
        'attachment_url',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'created_by',
    ];

    protected $casts = [
        'leave_type'  => LeaveType::class,
        'status'      => LeaveRequestStatus::class,
        'date_from'   => 'date',
        'date_to'     => 'date',
        'days_count'  => 'decimal:1',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::observe(LeaveRequestObserver::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', LeaveRequestStatus::Pending->value);
    }

    public function isPending(): bool
    {
        return $this->status === LeaveRequestStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === LeaveRequestStatus::Approved;
    }
}
