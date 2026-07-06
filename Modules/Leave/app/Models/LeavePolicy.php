<?php

namespace Modules\Leave\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Leave\Enums\LeaveType;

class LeavePolicy extends TenantAwareModel
{
    protected $table = 'leave_policies';

    protected $fillable = [
        'organization_id',
        'uuid',
        'leave_type',
        'name',
        'days_per_year',
        'carry_over_days',
        'min_advance_days',
        'max_consecutive_days',
        'requires_approval',
        'job_title_id',
        'department_id',
        'effective_from',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'leave_type'           => LeaveType::class,
        'days_per_year'        => 'decimal:1',
        'carry_over_days'      => 'decimal:1',
        'requires_approval'    => 'boolean',
        'is_active'            => 'boolean',
        'effective_from'       => 'date',
    ];

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(\Modules\JobTitle\Models\JobTitle::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\Modules\Department\Models\Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'policy_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Priority: job_title_id > department_id > org-level (both null) */
    public function scopeForEmployee($query, $employeeJobTitleId, $employeeDepartmentId)
    {
        return $query->where(function ($q) use ($employeeJobTitleId, $employeeDepartmentId) {
            $q->where('job_title_id', $employeeJobTitleId)
              ->orWhere(function ($q2) use ($employeeJobTitleId, $employeeDepartmentId) {
                  $q2->whereNull('job_title_id')
                     ->where('department_id', $employeeDepartmentId);
              })
              ->orWhere(function ($q3) {
                  $q3->whereNull('job_title_id')->whereNull('department_id');
              });
        });
    }
}
