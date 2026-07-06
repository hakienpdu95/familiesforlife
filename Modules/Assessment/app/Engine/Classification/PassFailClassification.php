<?php

namespace Modules\Assessment\Engine\Classification;

use Modules\Assessment\Models\PassFailConfig;
use Modules\Assessment\Engine\AggregatedResult;
use Modules\Assessment\Engine\ClassificationResult;
use Modules\Assessment\Engine\Contracts\ClassificationStrategy;
use Modules\Assessment\Engine\ScoringConfig;

class PassFailClassification implements ClassificationStrategy
{
    public function classify(ScoringConfig $config, AggregatedResult $aggregated, array $signalFlags): ClassificationResult
    {
        $pfConfig = PassFailConfig::findByCode($config->assessmentCode);

        if ($pfConfig === null) {
            return ClassificationResult::none();
        }

        $score  = $aggregated->overallScore ?? 0.0;
        $passed = $score >= $pfConfig->passing_score;
        $label  = $passed ? $pfConfig->label_pass : $pfConfig->label_fail;

        return ClassificationResult::passFail($passed, $label);
    }
}
