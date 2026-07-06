<?php

namespace Modules\Assessment\Engine\Contracts;

use Modules\Assessment\Engine\AggregatedResult;
use Modules\Assessment\Engine\ClassificationResult;
use Modules\Assessment\Engine\ScoringConfig;

interface ClassificationStrategy
{
    /**
     * Phân loại người dùng dựa trên kết quả aggregation.
     *
     * @param  array<string, bool>  $signalFlags
     */
    public function classify(ScoringConfig $config, AggregatedResult $aggregated, array $signalFlags): ClassificationResult;
}
