<?php

namespace Modules\Department\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Department\Models\Department;

class DepartmentUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Department $department,
    ) {}
}
