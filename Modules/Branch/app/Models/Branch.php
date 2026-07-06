<?php

namespace Modules\Branch\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Branch\Enums\BranchStatus;
use Modules\Branch\Enums\BranchType;
use Modules\Branch\Observers\BranchObserver;

class Branch extends TenantAwareModel
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'parent_id',
        'path',
        'depth',
        'manager_id',
        'order_column',
        'name',
        'code',
        'type',
        'status',
        'tax_code',
        'phone',
        'email',
        'fax',
        'province_code',
        'ward_code',
        'address',
        'lat',
        'lng',
        'timezone',
        'currency',
        'opened_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type'       => BranchType::class,
        'status'     => BranchStatus::class,
        'lat'        => 'float',
        'lng'        => 'float',
        'opened_at'  => 'date',
        'closed_at'  => 'date',
        'depth'      => 'integer',
    ];

    protected static function booted(): void
    {
        static::observe(BranchObserver::class);
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_code', 'ward_code');
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

    /** Lấy tất cả nhánh con (descendants) của branch này theo path. */
    public function scopeDescendantsOf($query, Branch $branch)
    {
        return $query->where('path', 'like', $branch->path . '%')
                     ->where('id', '!=', $branch->id);
    }

    /** Chỉ lấy các root branches (depth = 0). */
    public function scopeRoot($query)
    {
        return $query->where('depth', 0);
    }
}
