<?php

namespace Modules\Department\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Branch\Models\Branch;
use Modules\Department\Enums\DepartmentFunction;
use Modules\Department\Enums\DepartmentStatus;
use Modules\Department\Observers\DepartmentObserver;

class Department extends TenantAwareModel
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'branch_id',
        'parent_id',
        'path',
        'depth',
        'head_id',
        'deputy_head_id',
        'order_column',
        'name',
        'code',
        'function',
        'status',
        'merged_into_id',
        'budget_code',
        'headcount_limit',
        'description',
        'internal_phone',
        'internal_email',
        'effective_from',
        'effective_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'function'       => DepartmentFunction::class,
        'status'         => DepartmentStatus::class,
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'depth'          => 'integer',
    ];

    protected static function booted(): void
    {
        static::observe(DepartmentObserver::class);
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'merged_into_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /** Lấy tất cả nhánh con (descendants) theo materialized path. */
    public function scopeDescendantsOf($query, Department $dept)
    {
        return $query->where('path', 'like', $dept->path . '%')
                     ->where('id', '!=', $dept->id);
    }

    /** Chỉ lấy root departments (depth = 0). */
    public function scopeRoot($query)
    {
        return $query->where('depth', 0);
    }
}
