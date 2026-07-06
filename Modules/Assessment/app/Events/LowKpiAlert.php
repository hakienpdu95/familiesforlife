<?php

namespace Modules\Assessment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowKpiAlert
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $goalId,
        public readonly float $achievementPct,
        public readonly ?int $employeeId = null,
        public readonly ?int $organizationId = null,
    ) {}
}
