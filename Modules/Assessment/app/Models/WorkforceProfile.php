<?php

namespace Modules\Assessment\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Employee\Models\Employee;

class WorkforceProfile extends TenantAwareModel
{
    protected $table = 'workforce_profiles';

    public function resolveRouteBinding($value, $field = null): ?static
    {
        return $this->withoutTenant()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    protected $fillable = [
        'uuid',
        'organization_id',
        'user_id',
        'employee_id',
        'tdwcf_score',
        'tdwcf_maturity_level',
        'tdwcf_assessed_at',
        'score_d1_digital_literacy',
        'score_d2_data_literacy',
        'score_d3_ai_literacy',
        'score_d4_workflow',
        'score_d5_innovation',
        'score_d6_performance',
        'digital_score',
        'ai_score',
        'productivity_score',
        'innovation_score',
        'growth_score',
        'ai_readiness_score',
        'workforce_trust_score',
        'sandbox_sessions_total',
        'sandbox_hours_total',
        'sandbox_score_avg',
        'sandbox_last_completed_at',
        'certifications_count',
        'highest_cert_level',
        'highest_cert_issued_at',
        'highest_cert_expires_at',
        'kpi_achievement_avg',
        'impact_score',
        'career_goal',
        'current_learning_path',
        'profile_completeness_pct',
    ];

    protected function casts(): array
    {
        return [
            'tdwcf_assessed_at'          => 'datetime',
            'sandbox_last_completed_at'  => 'datetime',
            'highest_cert_issued_at'     => 'datetime',
            'highest_cert_expires_at'    => 'datetime',
            'tdwcf_score'                => 'float',
            'score_d1_digital_literacy'  => 'float',
            'score_d2_data_literacy'     => 'float',
            'score_d3_ai_literacy'       => 'float',
            'score_d4_workflow'          => 'float',
            'score_d5_innovation'        => 'float',
            'score_d6_performance'       => 'float',
            'digital_score'              => 'float',
            'ai_score'                   => 'float',
            'productivity_score'         => 'float',
            'innovation_score'           => 'float',
            'growth_score'               => 'float',
            'ai_readiness_score'         => 'float',
            'workforce_trust_score'      => 'float',
            'kpi_achievement_avg'        => 'float',
            'impact_score'               => 'float',
            'sandbox_score_avg'          => 'float',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(WorkforceProfileHistory::class)->orderByDesc('recorded_at');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(WorkforcePortfolio::class)->orderBy('sort_order');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(WorkforceCertification::class);
    }

    public function sandboxSessions(): HasMany
    {
        return $this->hasMany(SandboxSession::class);
    }

    public function recommendation(): HasOne
    {
        return $this->hasOne(WorkforceRecommendation::class)->latestOfMany();
    }

    // Workforce Trust Score = TDWCF×30% + Cert×25% + KPI×20% + Sandbox×15% + Portfolio×10%
    public function recalculateTrustScore(): float
    {
        $certScore = match ($this->highest_cert_level) {
            'LEADER'       => 100.0,
            'PROFESSIONAL' => 75.0,
            'PRACTITIONER' => 50.0,
            'FOUNDATION'   => 25.0,
            default        => 0.0,
        };

        return round(
            ($this->tdwcf_score ?? 0)      * 0.30 +
            $certScore                      * 0.25 +
            ($this->kpi_achievement_avg ?? 0) * 0.20 +
            ($this->sandbox_score_avg ?? 0)   * 0.15 +
            0                               * 0.10, // portfolio_score — calculated separately
        2);
    }
}
