<?php

namespace Modules\Assessment\Engine;

use Illuminate\Support\Facades\Log;

class MaturityDetector
{
    public function detect(ScoringConfig $config, float $overallScore): string
    {
        foreach ($config->maturityLevels as $level) {
            if ($overallScore >= $level->min_score && $overallScore <= $level->max_score) {
                return $level->level_code;
            }
        }

        // Fallback: lấy level gần nhất
        Log::warning('scoring.maturity.no_match', [
            'assessment_code' => $config->assessmentCode,
            'overall_score'   => $overallScore,
        ]);

        $sorted = $config->maturityLevels->sortBy('min_score');

        if ($overallScore < $sorted->first()->min_score) {
            return $sorted->first()->level_code;
        }

        return $sorted->last()->level_code;
    }
}
