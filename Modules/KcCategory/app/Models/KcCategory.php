<?php

namespace Modules\KcCategory\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KcCategory extends TenantAwareModel
{
    protected $table = 'kc_categories';

    protected $fillable = [
        'uuid',
        'organization_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'color_hex',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(KcCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(KcCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getDepthAttribute(): int
    {
        if (! $this->parent_id) {
            return 0;
        }
        if ($this->relationLoaded('parent') && $this->parent) {
            return $this->parent->depth + 1;
        }
        return 1;
    }
}
