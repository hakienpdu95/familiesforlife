<?php

namespace Modules\Task\Listeners;

use Modules\Task\Events\TaskUpdated;

class LogTaskUpdated
{
    public function handle(TaskUpdated $event): void
    {
        activity()
            ->on($event->task)
            ->withProperties([
                'project_id'      => $event->task->project_id,
                'organization_id' => $event->task->organization_id,
                'changed_fields'  => array_keys($event->task->getChanges()),
            ])
            ->log('task.updated');
    }
}
