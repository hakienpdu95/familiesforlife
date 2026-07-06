<?php

namespace Modules\Assessment\Engine;

class ScoreNormalizer
{
    /**
     * Raw → Normalized per domain (0–100), sau đó tính weighted overall.
     *
     * @param array<string, int>   $rawScores   output của RuleEngine
     * @return array{domainScores: array<string, DomainScoreResult>, overallScore: float}
     */
    public function normalize(ScoringConfig $config, array $rawScores): array
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

            // Clamp [0, 100]
            $normalized = max(0.0, min(100.0, $normalized));

            $domainScores[$code] = new DomainScoreResult(
                domainCode:      $code,
                rawScore:        $raw,
                normalizedScore: $normalized,
            );

            $overallScore += $normalized * $domain->weight;
        }

        $overallScore = max(0.0, min(100.0, $overallScore));

        return ['domainScores' => $domainScores, 'overallScore' => $overallScore];
    }
}
