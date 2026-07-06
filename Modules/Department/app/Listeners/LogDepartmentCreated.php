<?php

namespace Modules\Department\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Department\Events\DepartmentCreated;

class LogDepartmentCreated
{
    public function handle(DepartmentCreated $event): void
    {
        ActivityLogger::info('Department', 'department_created', $event->department, [
            'department_id'   => $event->department->id,
            'name'            => $event->department->name,
            'code'            => $event->department->code,
            'organization_id' => $event->department->organization_id,
        ]);
    }
}
