<?php

namespace Modules\Sop\Listeners;

use Modules\Sop\Events\SopProcessUpdated;

class LogSopProcessUpdated
{
    public function handle(SopProcessUpdated $event): void
    {
        activity()
            ->on($event->sop)
            ->log('sop_process.updated');
    }
}
