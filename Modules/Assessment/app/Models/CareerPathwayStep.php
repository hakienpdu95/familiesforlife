<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// organization_id nullable (null = global) — must NOT extend TenantAwareModel
class CareerPathwayStep extends Model
{
    protected $table = 'career_pathway_steps';

    protected $fillable = [
        'organization_id',
        'from_level',
        'to_level',
        'step_order',
        'title',
        'description',
        'required_cert_code',
        'recommended_kc_tag',
        'recommended_sandbox_env_code',
        'estimated_weeks',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'step_order'      => 'integer',
            'estimated_weeks' => 'integer',
        ];
    }

    public function scopeAvailableForOrg(Builder $query, int $organizationId): Builder
    {
        return $query->where(function (Builder $q) use ($organizationId) {
            $q->whereNull('organization_id')
              ->orWhere('organization_id', $organizationId);
        });
    }

    public function scopeFromLevel(Builder $query, string $level): Builder
    {
        return $query->where('from_level', $level);
    }
}
