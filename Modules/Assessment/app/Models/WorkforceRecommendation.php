<?php

namespace Modules\Assessment\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkforceRecommendation extends TenantAwareModel
{
    protected $table = 'workforce_recommendations';

    protected $fillable = [
        'organization_id',
        'workforce_profile_id',
        'generated_at',
        'provider',
        'model',
        'context_hash',
        'recommendations',
        'input_tokens',
        'output_tokens',
        'is_stale',
    ];

    protected function casts(): array
    {
        return [
            'generated_at'   => 'datetime',
            'recommendations' => 'array',
            'is_stale'        => 'boolean',
            'input_tokens'    => 'integer',
            'output_tokens'   => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function workforceProfile(): BelongsTo
    {
        return $this->belongsTo(WorkforceProfile::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkforceRecommendationItem::class, 'workforce_recommendation_id');
    }

    // ── Business Logic ────────────────────────────────────────────────────────

    /**
     * Build a stable hash representing the inputs used to generate recommendations.
     * If the profile changes in any of these dimensions the hash changes and the
     * cached recommendation becomes stale.
     */
    public static function computeContextHash(WorkforceProfile $p): string
    {
        return md5(json_encode([
            $p->tdwcf_score,
            $p->score_d1_digital_literacy,
            $p->score_d2_data_literacy,
            $p->score_d3_ai_literacy,
            $p->score_d4_workflow,
            $p->score_d5_innovation,
            $p->score_d6_performance,
            $p->tdwcf_maturity_level,
            $p->employee?->job_title_id,
        ]));
    }

    /**
     * Return true when this recommendation is still valid for the given profile.
     */
    public function isStillFresh(WorkforceProfile $p): bool
    {
        return ! $this->is_stale && $this->context_hash === static::computeContextHash($p);
    }
}
