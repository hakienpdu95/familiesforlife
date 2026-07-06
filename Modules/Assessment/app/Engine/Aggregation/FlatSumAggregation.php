<?php

namespace Modules\Assessment\Engine\Aggregation;

use Modules\Assessment\Engine\AggregatedResult;
use Modules\Assessment\Engine\Contracts\AggregationStrategy;
use Modules\Assessment\Engine\DomainScoreResult;
use Modules\Assessment\Engine\ScoringConfig;

class FlatSumAggregation implements AggregationStrategy
{
    public function aggregate(ScoringConfig $config, array $rawScores, array $weights): AggregatedResult
    {
        $total = array_sum($rawScores);
        $total = max(0.0, min(100.0, (float) $total));

        return new AggregatedResult(
            domainScores: [],
            sectionScores: [],
            overallScore: $total,
        );
    }
}
