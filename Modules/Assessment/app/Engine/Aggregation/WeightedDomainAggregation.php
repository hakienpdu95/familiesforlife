<?php

namespace Modules\Assessment\Engine\Aggregation;

use Modules\Assessment\Engine\AggregatedResult;
use Modules\Assessment\Engine\Contracts\AggregationStrategy;
use Modules\Assessment\Engine\DomainScoreResult;
use Modules\Assessment\Engine\ScoringConfig;

class WeightedDomainAggregation implements AggregationStrategy
{
    /**
     * RawDomain(d)        = Σ Fi (feature thuộc domain d)
     * NormalizedDomain(d) = ((Raw - Min)/(Max - Min)) × 100  → CLAMP [0,100]
     * OverallScore        = Σ ( NormalizedDomain(d) × Weight(d) )
     */
    public function aggregate(ScoringConfig $config, array $rawScores, array $weights): AggregatedResult
    {
        $domainScores = [];
        $overallScore = 0.0;

        foreach ($config->domains as $domain) {
            $code = $domain->domain_code;
            $raw  = $rawScores[$code] ?? 0;

            $range      = $domain->max_score - $domain->min_score;
            $normalized = $range > 0
                ? (($raw - $domain->min_score) / $range) * 100
                : 0.0;
            $normalized = max(0.0, min(100.0, $normalized));

            $domainScores[$code] = new DomainScoreResult(
                domainCode:      $code,
                rawScore:        $raw,
                normalizedScore: $normalized,
            );

            // Wi từ feature_weights (domain level), fallback về domain.weight (cũ)
            $weight = $weights[$code] ?? (float) ($domain->getAttribute('weight') ?? 0.0);
            $overallScore += $normalized * $weight;
        }

        $overallScore = max(0.0, min(100.0, $overallScore));

        return new AggregatedResult(
            domainScores: $domainScores,
            sectionScores: [],
            overallScore: $overallScore,
        );
    }
}
