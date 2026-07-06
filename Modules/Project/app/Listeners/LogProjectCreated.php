<?php

namespace Modules\Project\Listeners;

use Modules\Project\Events\ProjectCreated;

class LogProjectCreated
{
    public function handle(ProjectCreated $event): void
    {
        activity()->on($event->project)->log('project.created');
    }
}
