<?php

namespace Modules\Assessment\Engine\Classification;

use Modules\Assessment\Engine\AggregatedResult;
use Modules\Assessment\Engine\ClassificationResult;
use Modules\Assessment\Engine\Contracts\ClassificationStrategy;
use Modules\Assessment\Engine\ScoringConfig;

class NoneClassification implements ClassificationStrategy
{
    public function classify(ScoringConfig $config, AggregatedResult $aggregated, array $signalFlags): ClassificationResult
    {
        return ClassificationResult::none();
    }
}
