<?php

namespace Modules\Assessment\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkforceCertification extends TenantAwareModel
{
    protected $table = 'workforce_certifications';

    protected $fillable = [
        'uuid',
        'organization_id',
        'workforce_profile_id',
        'cert_definition_id',
        'assessment_score_at_issue',
        'sandbox_score_at_issue',
        'impact_score_at_issue',
        'portfolio_score_at_issue',
        'composite_score_at_issue',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revoked_reason',
        'certificate_number',
        'qr_code_url',
        'digital_badge_url',
        'issued_by',
        'human_reviewer_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at'                  => 'datetime',
            'expires_at'                 => 'datetime',
            'revoked_at'                 => 'datetime',
            'reviewed_at'                => 'datetime',
            'assessment_score_at_issue'  => 'float',
            'sandbox_score_at_issue'     => 'float',
            'impact_score_at_issue'      => 'float',
            'portfolio_score_at_issue'   => 'float',
            'composite_score_at_issue'   => 'float',
        ];
    }

    // Composite Score = Assessment×30% + Sandbox×25% + Impact×25% + Portfolio×20%
    public function calculateCompositeScore(): float
    {
        return round(
            ($this->assessment_score_at_issue ?? 0) * 0.30 +
            ($this->sandbox_score_at_issue    ?? 0) * 0.25 +
            ($this->impact_score_at_issue     ?? 0) * 0.25 +
            ($this->portfolio_score_at_issue  ?? 0) * 0.20,
        2);
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        return $this->withoutTenant()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CertificationDefinition::class, 'cert_definition_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WorkforceProfile::class, 'workforce_profile_id');
    }
}
