<?php

namespace Modules\Employee\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Employee\Models\Employee;

class EmployeeUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Employee $employee) {}
}
