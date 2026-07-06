<?php

namespace Modules\Project\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Branch\Models\Branch;
use Modules\Department\Models\Department;
use Modules\Employee\Models\Employee;
use Modules\Project\Enums\ProjectPriority;
use Modules\Project\Enums\ProjectStatus;

class Project extends TenantAwareModel
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'branch_id',
        'department_id',
        'owner_id',
        'code',
        'name',
        'description',
        'category',
        'vertical_code',
        'status',
        'priority',
        'start_date',
        'end_date',
        'completed_at',
        'budget',
        'currency',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'       => ProjectStatus::class,
        'priority'     => ProjectPriority::class,
        'start_date'   => 'date',
        'end_date'     => 'date',
        'completed_at' => 'datetime',
        'budget'       => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class)->whereNull('left_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
