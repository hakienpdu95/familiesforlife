<?php

namespace Modules\Assessment\Engine;

class RecommendationEngine
{
    /**
     * @param  array<string, DomainScoreResult>  $domainScores
     * @return RecommendationResult[]
     */
    public function evaluate(ScoringConfig $config, array $domainScores): array
    {
        $results = [];

        foreach ($config->recommendations as $rule) {
            $domain = $domainScores[$rule->trigger_domain] ?? null;
            if ($domain === null) {
                continue;
            }

            if ($domain->normalizedScore < $rule->threshold_score) {
                $results[] = new RecommendationResult(
                    code:        $rule->recommendation_code,
                    label:       $rule->label,
                    description: $rule->description,
                    priority:    $rule->priority,
                );
            }
        }

        usort($results, fn ($a, $b) => $a->priority <=> $b->priority);

        return $results;
    }
}
