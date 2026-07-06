<?php

namespace Modules\Leave\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Employee\Models\Employee;
use Modules\Leave\Enums\LeaveType;

class LeaveBalance extends TenantAwareModel
{
    protected $table = 'leave_balances';

    protected $fillable = [
        'organization_id',
        'employee_id',
        'policy_id',
        'leave_type',
        'year',
        'entitled_days',
        'used_days',
        'pending_days',
        'carried_over',
        'adjusted',
    ];

    protected $casts = [
        'leave_type'    => LeaveType::class,
        'year'          => 'integer',
        'entitled_days' => 'decimal:1',
        'used_days'     => 'decimal:1',
        'pending_days'  => 'decimal:1',
        'carried_over'  => 'decimal:1',
        'adjusted'      => 'decimal:1',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'balance_id');
    }

    /** remaining = entitled + carried_over + adjusted - used - pending */
    public function getRemainingDaysAttribute(): float
    {
        return (float) ($this->entitled_days + $this->carried_over + $this->adjusted
            - $this->used_days - $this->pending_days);
    }
}
