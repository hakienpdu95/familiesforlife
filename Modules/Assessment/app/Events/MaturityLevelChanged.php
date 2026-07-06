<?php

namespace Modules\Assessment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Employee\Models\Employee;

class MaturityLevelChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Employee $employee,
        public readonly string $oldLevel,
        public readonly string $newLevel,
    ) {}
}
