<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// organization_id nullable (null = global template) — must NOT extend TenantAwareModel
class CertificationDefinition extends Model
{
    protected $table = 'certification_definitions';

    protected $fillable = [
        'uuid',
        'organization_id',
        'cert_code',
        'cert_type_code',
        'name',
        'level_code',
        'level_order',
        'description',
        'validity_months',
        'min_workforce_score',
        'min_kpi_achievement_pct',
        'min_sandbox_hours',
        'min_sandbox_score',
        'requires_impact_score',
        'requires_portfolio_approval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_impact_score'       => 'boolean',
            'requires_portfolio_approval' => 'boolean',
            'is_active'                   => 'boolean',
            'min_workforce_score'         => 'float',
            'min_kpi_achievement_pct'     => 'float',
            'min_sandbox_score'           => 'float',
        ];
    }

    // Returns global templates OR org-specific definitions for the given org.
    public function scopeAvailableForOrg(Builder $query, int $organizationId): Builder
    {
        return $query->where(function (Builder $q) use ($organizationId) {
            $q->whereNull('organization_id')
              ->orWhere('organization_id', $organizationId);
        });
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(WorkforceCertification::class, 'cert_definition_id');
    }
}
