<?php

namespace Modules\KpiGoal\Queries;

class ListKpiGoalsQuery
{
    public function __construct(
        public readonly ?int    $employee_id  = null,
        public readonly ?string $cycle_label  = null,
        public readonly ?string $status       = null,
        public readonly ?string $goal_type    = null,
    ) {}
}
