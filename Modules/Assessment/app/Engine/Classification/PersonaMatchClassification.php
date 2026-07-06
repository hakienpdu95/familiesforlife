<?php

namespace Modules\Assessment\Engine\Classification;

use Modules\Assessment\Models\Persona;
use Modules\Assessment\Engine\AggregatedResult;
use Modules\Assessment\Engine\ClassificationResult;
use Modules\Assessment\Engine\Contracts\ClassificationStrategy;
use Modules\Assessment\Engine\ScoringConfig;

class PersonaMatchClassification implements ClassificationStrategy
{
    /**
     * match_score(persona) = số điều kiện thỏa / tổng điều kiện
     * → chọn persona match_score cao nhất (tie → sort_order thấp hơn)
     */
    public function classify(ScoringConfig $config, AggregatedResult $aggregated, array $signalFlags): ClassificationResult
    {
        $personas = Persona::forAssessment($config->assessmentCode)->with('conditions')->orderBy('sort_order')->get();

        if ($personas->isEmpty()) {
            return ClassificationResult::none();
        }

        $bestPersona   = null;
        $bestMatchScore = -1.0;

        foreach ($personas as $persona) {
            $conditions  = $persona->conditions;
            $total       = $conditions->count();

            if ($total === 0) {
                continue;
            }

            $matched = 0;
            foreach ($conditions as $cond) {
                if ($this->evaluateCondition($cond, $aggregated, $signalFlags)) {
                    $matched++;
                }
            }

            $matchScore = $matched / $total;

            if ($matchScore > $bestMatchScore) {
                $bestMatchScore = $matchScore;
                $bestPersona    = $persona;
            }
        }

        // Soft match: pick the best-fit persona even if not all conditions pass.
        // Return none() only when no personas are defined (or none have conditions).
        if ($bestPersona === null) {
            return ClassificationResult::none();
        }

        return ClassificationResult::persona(
            $bestPersona->persona_code,
            round($bestMatchScore * 100, 2),
            $bestPersona->label,
        );
    }

    private function evaluateCondition($cond, AggregatedResult $aggregated, array $signalFlags): bool
    {
        $value = match ($cond->target_type) {
            'overall'      => $aggregated->overallScore ?? 0.0,
            'domain'       => $aggregated->domainScores[$cond->target_code]?->normalizedScore ?? 0.0,
            'section'      => $aggregated->sectionScores[$cond->target_code]?->normalizedScore ?? 0.0,
            'signal_flag'  => null,
            default        => null,
        };

        if ($cond->target_type === 'signal_flag') {
            $flagValue = $signalFlags[$cond->target_code] ?? false;
            return $flagValue === $cond->flag_value;
        }

        if ($value === null || $cond->threshold_value === null) {
            return false;
        }

        return match ($cond->operator) {
            '<'  => $value < $cond->threshold_value,
            '<=' => $value <= $cond->threshold_value,
            '='  => abs($value - $cond->threshold_value) < 0.001,
            '>=' => $value >= $cond->threshold_value,
            '>'  => $value > $cond->threshold_value,
            default => false,
        };
    }
}
