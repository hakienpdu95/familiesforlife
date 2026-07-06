<?php

namespace Modules\Project\Listeners;

use Modules\Project\Events\ProjectUpdated;

class LogProjectUpdated
{
    public function handle(ProjectUpdated $event): void
    {
        activity()->on($event->project)->log('project.updated');
    }
}
