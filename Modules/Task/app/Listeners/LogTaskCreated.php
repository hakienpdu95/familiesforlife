<?php

namespace Modules\Task\Listeners;

use Modules\Task\Events\TaskCreated;

class LogTaskCreated
{
    public function handle(TaskCreated $event): void
    {
        activity()
            ->on($event->task)
            ->withProperties([
                'project_id'      => $event->task->project_id,
                'organization_id' => $event->task->organization_id,
            ])
            ->log('task.created');
    }
}
