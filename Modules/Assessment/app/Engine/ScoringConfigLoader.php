<?php

namespace Modules\Assessment\Engine;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Assessment\Exceptions\InvalidScoringConfigException;
use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\MaturityLevel;
use Modules\Assessment\Models\PainPointRule;
use Modules\Assessment\Models\RecommendationRule;
use Modules\Assessment\Models\ScoreBand;
use Modules\Assessment\Models\ScoreRule;
use Modules\Survey\Models\SurveySection;


class ScoringConfigLoader
{
    public function load(string $assessmentCode): ScoringConfig
    {
        $assessment      = Assessment::findByCode($assessmentCode);
        $domains         = AssessmentDomain::forAssessment($assessmentCode)->ordered()->get();
        $scoreBands      = ScoreBand::forAssessment($assessmentCode)->ordered()->get();
        $maturityLevels  = MaturityLevel::forAssessment($assessmentCode)->ordered()->get();
        $scoreRules      = ScoreRule::forAssessment($assessmentCode)
                            ->with(['options', 'numericRanges'])
                            ->get();
        $painPointRules  = PainPointRule::forAssessment($assessmentCode)->get();
        $recommendations = RecommendationRule::forAssessment($assessmentCode)->get();
        $sections        = SurveySection::where('assessment_code', $assessmentCode)
                            ->orderBy('sort_order')
                            ->get();

        $this->validate($assessmentCode, $assessment, $domains, $maturityLevels, $scoreBands);

        return new ScoringConfig(
            assessmentCode:  $assessmentCode,
            assessment:      $assessment,
            domains:         $domains,
            scoreBands:      $scoreBands,
            maturityLevels:  $maturityLevels,
            scoreRules:      $scoreRules,
            painPointRules:  $painPointRules,
            recommendations: $recommendations,
            sections:        $sections,
        );
    }

    private function validate(
        string      $code,
        ?Assessment $assessment,
        Collection  $domains,
        Collection  $maturityLevels,
        Collection  $scoreBands,
    ): void {
        $aggregationModel = $assessment?->aggregation_model ?? 'weighted_domain';

        // weighted_domain cần có domains
        if ($aggregationModel === 'weighted_domain') {
            if ($domains->isEmpty()) {
                throw new InvalidScoringConfigException("assessment_code '{$code}' không có domain nào.");
            }

            // Validate weight tổng = 1.0 khi dùng feature_weights
            // (Với schema cũ dùng assessment_domains.weight)
            $weightSum = $domains->sum('weight');
            if ($weightSum > 0 && abs($weightSum - 1.0) > 0.0001) {
                throw new InvalidScoringConfigException(
                    "Tổng weight domains của '{$code}' = {$weightSum}, phải bằng 1.0000."
                );
            }

            foreach ($domains as $domain) {
                if ($domain->min_score >= $domain->max_score) {
                    throw new InvalidScoringConfigException(
                        "Domain '{$domain->domain_code}': min_score ({$domain->min_score}) >= max_score ({$domain->max_score})."
                    );
                }
            }
        }

        if ($scoreBands->isEmpty() && $maturityLevels->isEmpty()) {
            $classType = $assessment?->classification_type ?? 'score_band';
            if ($classType === 'score_band') {
                Log::warning('scoring.config.no_bands', ['assessment_code' => $code]);
            }
        }
    }
}
