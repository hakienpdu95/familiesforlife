<?php

namespace Modules\Assessment\Events;

use Modules\KpiGoal\Models\AiImpactSnapshot;

class ImpactSnapshotRecorded
{
    public function __construct(
        public readonly AiImpactSnapshot $snapshot,
    ) {}
}
