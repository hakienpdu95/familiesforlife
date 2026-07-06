<?php

namespace Modules\Employee\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Enums\EmploymentType;
use Modules\Employee\Observers\EmployeeObserver;
use Spatie\MediaLibrary\HasMedia;

class Employee extends TenantAwareModel implements HasMedia
{
    use HasTenantMedia;
    public static bool $skipHistoryTracking = false;

    protected $fillable = [
        'uuid',
        'organization_id',
        'user_id',
        'branch_id',
        'department_id',
        'job_title_id',
        'manager_id',
        'employee_code',
        'full_name',
        'email',
        'personal_email',
        'address',
        'phone',
        'gender',
        'date_of_birth',
        'national_id',
        'national_id_issued',
        'tax_code',
        'bank_account',
        'bank_name',
        'locale',
        'avatar_url',
        'status',
        'employment_type',
        'hired_at',
        'probation_end_date',
        'contract_start',
        'contract_end',
        'left_at',
        'salary_base',
        'salary_currency',
        'work_location',
        'emergency_contact_name',
        'emergency_contact_phone',
        'resigned_at',
        'resignation_reason',
        'notes',
        'digital_competency_score',
        'digital_maturity_level',
        'latest_assessment_result_id',
        'last_assessed_at',
        'snap_branch_name',
        'snap_dept_name',
        'snap_job_title',
        'snap_job_level',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'             => EmployeeStatus::class,
        'employment_type'    => EmploymentType::class,
        'date_of_birth'      => 'date',
        'national_id_issued' => 'date',
        'hired_at'           => 'date',
        'probation_end_date' => 'date',
        'contract_start'     => 'date',
        'contract_end'       => 'date',
        'left_at'            => 'date',
        'resigned_at'        => 'date',
        'salary_base'                  => 'decimal:2',
        'snap_job_level'               => 'integer',
        'digital_competency_score'     => 'decimal:2',
        'last_assessed_at'             => 'datetime',
        'latest_assessment_result_id'  => 'integer',
    ];

    protected static function booted(): void
    {
        static::observe(EmployeeObserver::class);
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\Modules\Department\Models\Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(\Modules\JobTitle\Models\JobTitle::class)->withoutGlobalScopes();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function employeeDepartments(): HasMany
    {
        return $this->hasMany(EmployeeDepartment::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class)->latest('effective_date');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', EmployeeStatus::Active->value);
    }

    public function scopeWorking($query)
    {
        return $query->whereIn('status', [
            EmployeeStatus::Active->value,
            EmployeeStatus::Probation->value,
            EmployeeStatus::OnLeave->value,
        ]);
    }

    public function hasDirectReports(): bool
    {
        return $this->subordinates()->working()->exists();
    }
}
