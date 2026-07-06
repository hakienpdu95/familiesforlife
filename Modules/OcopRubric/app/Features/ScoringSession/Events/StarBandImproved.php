<?php

namespace Modules\OcopRubric\Features\ScoringSession\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\OcopRubric\Models\OcopProduct;

class StarBandImproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OcopProduct $product,
        public readonly int $previousStarRank,
        public readonly int $newStarRank,
    ) {}
}
