<?php

namespace Modules\JobTitle\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Assessment\Models\JobTitleDomainRequirement;
use Modules\JobTitle\Enums\JobTitleCategory;

class JobTitle extends TenantAwareModel
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'code',
        'name',
        'category',
        'level',
        'description',
        'is_system',
        'is_locked',
        'is_active',
    ];

    protected $casts = [
        'category'  => JobTitleCategory::class,
        'level'     => 'integer',
        'is_system' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function domainRequirements(): HasMany
    {
        return $this->hasMany(JobTitleDomainRequirement::class, 'job_title_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }
}
