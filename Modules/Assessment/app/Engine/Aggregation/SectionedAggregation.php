<?php

namespace Modules\Assessment\Engine\Aggregation;

use Modules\Assessment\Engine\AggregatedResult;
use Modules\Assessment\Engine\Contracts\AggregationStrategy;
use Modules\Assessment\Engine\DomainScoreResult;
use Modules\Assessment\Engine\ScoringConfig;

class SectionedAggregation implements AggregationStrategy
{
    /**
     * SectionScore(s) = Σ Fi (feature thuộc section s)
     * Không có overall score — mỗi section ra 1 điểm độc lập.
     */
    public function aggregate(ScoringConfig $config, array $rawScores, array $weights): AggregatedResult
    {
        $sectionScores = [];

        foreach ($config->sections as $section) {
            $code = $section->section_code ?? (string) $section->id;
            $raw  = $rawScores[$code] ?? 0;

            $range      = ($section->max_score ?? 100) - ($section->min_score ?? 0);
            $normalized = $range > 0
                ? (($raw - ($section->min_score ?? 0)) / $range) * 100
                : 0.0;
            $normalized = max(0.0, min(100.0, $normalized));

            $sectionScores[$code] = new DomainScoreResult(
                domainCode:      $code,
                rawScore:        $raw,
                normalizedScore: $normalized,
            );
        }

        return new AggregatedResult(
            domainScores: [],
            sectionScores: $sectionScores,
            overallScore: null,
        );
    }
}
