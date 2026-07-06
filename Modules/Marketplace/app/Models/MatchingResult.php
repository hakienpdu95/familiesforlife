<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchingResult extends Model
{
    protected $table = 'matching_results';

    protected $fillable = [
        'organization_id',
        'workforce_profile_id',
        'mkt_listing_id',
        'mkt_applicant_id',
        'competency_match',
        'certification_match',
        'experience_match',
        'ai_readiness_match',
        'career_goal_match',
        'matching_score',
        'match_level',
        'calculated_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'calculated_at'       => 'datetime',
            'competency_match'    => 'float',
            'certification_match' => 'float',
            'experience_match'    => 'float',
            'ai_readiness_match'  => 'float',
            'career_goal_match'   => 'float',
            'matching_score'      => 'float',
        ];
    }

    // Matching Score = Năng lực×40% + Chứng nhận×20% + Kinh nghiệm×15% + AI Readiness×15% + Career Goal×10%
    public function calculateMatchingScore(): float
    {
        return round(
            ($this->competency_match    ?? 0) * 0.40 +
            ($this->certification_match ?? 0) * 0.20 +
            ($this->experience_match    ?? 0) * 0.15 +
            ($this->ai_readiness_match  ?? 0) * 0.15 +
            ($this->career_goal_match   ?? 0) * 0.10,
        2);
    }

    public function resolveMatchLevel(): string
    {
        $score = $this->matching_score ?? 0;
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 75 => 'strong',
            $score >= 60 => 'potential',
            $score >= 40 => 'development',
            default      => 'not_recommended',
        };
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MktListing::class, 'mkt_listing_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MktApplicant::class, 'mkt_applicant_id');
    }
}
