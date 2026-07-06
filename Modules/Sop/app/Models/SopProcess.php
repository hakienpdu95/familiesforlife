<?php

namespace Modules\Sop\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Branch\Models\Branch;
use Modules\Department\Models\Department;
use Modules\Sop\Enums\SopStatus;
use Modules\Sop\Enums\SopType;

class SopProcess extends TenantAwareModel
{
    protected $table = 'sop_processes';

    protected $fillable = [
        'organization_id',
        'uuid',
        'branch_id',
        'department_id',
        'owner_id',
        'code',
        'title',
        'description',
        'type',
        'status',
        'version',
        'approved_by',
        'approved_at',
        'effective_date',
        'expired_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'         => SopStatus::class,
        'type'           => SopType::class,
        'effective_date' => 'date',
        'expired_date'   => 'date',
        'approved_at'    => 'datetime',
    ];

    // Route model binding dùng UUID — không expose BIGINT id
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SopStep::class, 'sop_id')->orderBy('position');
    }

    public function activeSteps(): HasMany
    {
        return $this->steps()->where('is_active', true);
    }

    public function connectors(): HasMany
    {
        return $this->hasMany(SopStepConnector::class, 'sop_id');
    }

    public function sopRelations(): HasMany
    {
        return $this->hasMany(SopRelation::class, 'sop_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SopVersion::class, 'sop_id')->orderByDesc('version_number');
    }

    public function latestApprovedVersion(): HasMany
    {
        return $this->versions()->where('status', 'approved')->latest('version_number');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', SopStatus::Approved->value);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', SopStatus::Draft->value);
    }
}
