<?php

namespace Modules\Assessment\Engine;

use Modules\Assessment\Engine\Aggregation\FlatSumAggregation;
use Modules\Assessment\Engine\Aggregation\SectionedAggregation;
use Modules\Assessment\Engine\Aggregation\WeightedDomainAggregation;
use Modules\Assessment\Engine\Contracts\AggregationStrategy;

class AggregationFactory
{
    public function make(string $model): AggregationStrategy
    {
        return match ($model) {
            'weighted_domain' => new WeightedDomainAggregation(),
            'sectioned'       => new SectionedAggregation(),
            default           => new FlatSumAggregation(),   // flat_sum + unknown
        };
    }
}
