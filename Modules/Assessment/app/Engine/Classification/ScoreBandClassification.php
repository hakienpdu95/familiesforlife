<?php

namespace Modules\Assessment\Engine\Classification;

use Illuminate\Support\Facades\Log;
use Modules\Assessment\Engine\AggregatedResult;
use Modules\Assessment\Engine\ClassificationResult;
use Modules\Assessment\Engine\Contracts\ClassificationStrategy;
use Modules\Assessment\Engine\ScoringConfig;

class ScoreBandClassification implements ClassificationStrategy
{
    /**
     * IF score >= band.min_score AND score <= band.max_score → classify = band.band_code
     */
    public function classify(ScoringConfig $config, AggregatedResult $aggregated, array $signalFlags): ClassificationResult
    {
        $score = $aggregated->overallScore ?? 0.0;

        // Tầng ưu tiên 1: score_bands (spec mới)
        if ($config->scoreBands->isNotEmpty()) {
            foreach ($config->scoreBands as $band) {
                if ($band->contains($score)) {
                    return ClassificationResult::scoreBand($band->band_code, $band->label);
                }
            }

            // Fallback: band gần nhất
            Log::warning('scoring.classification.no_band_match', [
                'assessment_code' => $config->assessmentCode,
                'overall_score'   => $score,
            ]);

            $sorted = $config->scoreBands->sortBy('min_score');
            $band   = $score < $sorted->first()->min_score
                ? $sorted->first()
                : $sorted->last();

            return ClassificationResult::scoreBand($band->band_code, $band->label);
        }

        // Tầng fallback: maturity_levels (schema cũ)
        if ($config->maturityLevels->isNotEmpty()) {
            foreach ($config->maturityLevels as $level) {
                if ($score >= $level->min_score && $score <= $level->max_score) {
                    return ClassificationResult::scoreBand($level->level_code, $level->label);
                }
            }
            $sorted = $config->maturityLevels->sortBy('min_score');
            $level  = $score < $sorted->first()->min_score ? $sorted->first() : $sorted->last();

            return ClassificationResult::scoreBand($level->level_code, $level->label);
        }

        return ClassificationResult::none();
    }
}
