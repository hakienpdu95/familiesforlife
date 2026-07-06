<?php

namespace Modules\Assessment\Engine;

class WeightRepository
{
    /**
     * Load domain weights for an assessment from assessment_domains.weight.
     *
     * @return array{weights: array<string,float>, version: int}
     */
    public function loadActive(string $assessmentCode, ScoringConfig $config): array
    {
        $weights = [];
        foreach ($config->domains as $domain) {
            $w = $domain->getAttribute('weight');
            if ($w !== null) {
                $weights[$domain->domain_code] = (float) $w;
            }
        }

        return ['weights' => $weights, 'version' => $config->assessment?->version ?? 1];
    }
}
