<?php

namespace Modules\KpiGoal\Queries;

class KpiLeaderboardQuery
{
    public function __construct(
        public readonly string $cycle_label,
        public readonly ?int   $department_id = null,
    ) {}
}
