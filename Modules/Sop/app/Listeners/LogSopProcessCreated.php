<?php

namespace Modules\Sop\Listeners;

use Modules\Sop\Events\SopProcessCreated;

class LogSopProcessCreated
{
    public function handle(SopProcessCreated $event): void
    {
        activity()
            ->on($event->sop)
            ->log('sop_process.created');
    }
}
