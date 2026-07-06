<?php

namespace Modules\Branch\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Branch\Events\BranchCreated;

class LogBranchCreated
{
    public function handle(BranchCreated $event): void
    {
        ActivityLogger::info('Branch', 'branch_created', $event->branch, [
            'branch_id'       => $event->branch->id,
            'name'            => $event->branch->name,
            'code'            => $event->branch->code,
            'organization_id' => $event->branch->organization_id,
        ]);
    }
}
