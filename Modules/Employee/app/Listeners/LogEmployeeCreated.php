<?php

namespace Modules\Employee\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Employee\Events\EmployeeCreated;

class LogEmployeeCreated
{
    public function handle(EmployeeCreated $event): void
    {
        $e = $event->employee;
        ActivityLogger::info('Employee', 'employee_created', $e, [
            'organization_id' => $e->organization_id,
            'branch_id'       => $e->branch_id,
            'department_id'   => $e->department_id,
            'job_title_id'    => $e->job_title_id,
        ]);
    }
}
