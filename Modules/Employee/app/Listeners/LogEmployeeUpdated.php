<?php

namespace Modules\Employee\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Employee\Events\EmployeeUpdated;

class LogEmployeeUpdated
{
    public function handle(EmployeeUpdated $event): void
    {
        $e = $event->employee;
        ActivityLogger::info('Employee', 'employee_updated', $e, [
            'organization_id' => $e->organization_id,
            'changed_fields'  => implode(',', array_keys($e->getChanges())),
        ]);
    }
}
