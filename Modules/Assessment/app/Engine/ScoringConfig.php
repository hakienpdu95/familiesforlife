<?php

namespace Modules\Assessment\Engine;

use Illuminate\Database\Eloquent\Collection;
use Modules\Assessment\Models\Assessment;

readonly class ScoringConfig
{
    /**
     * @param  Collection  $domains         assessment_domains (weighted_domain)
     * @param  Collection  $scoreBands      score_bands (score_band classification)
     * @param  Collection  $maturityLevels  maturity_levels (fallback cũ)
     * @param  Collection  $scoreRules      score_rules + options + numeric_ranges
     * @param  Collection  $painPointRules  pain_point_rules
     * @param  Collection  $recommendations recommendation_rules
     * @param  Collection  $sections        survey_sections (sectioned aggregation)
     */
    public function __construct(
        public readonly string      $assessmentCode,
        public readonly ?Assessment $assessment,
        public readonly Collection  $domains,
        public readonly Collection  $scoreBands,
        public readonly Collection  $maturityLevels,
        public readonly Collection  $scoreRules,
        public readonly Collection  $painPointRules,
        public readonly Collection  $recommendations,
        public readonly Collection  $sections,
    ) {}

    public function aggregationModel(): string
    {
        return $this->assessment?->aggregation_model ?? 'weighted_domain';
    }

    public function classificationType(): string
    {
        return $this->assessment?->classification_type ?? 'score_band';
    }

    public function hasScoring(): bool
    {
        return $this->assessment !== null && $this->assessment->has_scoring;
    }
}
