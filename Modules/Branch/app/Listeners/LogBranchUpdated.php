<?php

namespace Modules\Branch\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Branch\Events\BranchUpdated;

class LogBranchUpdated
{
    public function handle(BranchUpdated $event): void
    {
        ActivityLogger::info('Branch', 'branch_updated', $event->branch, [
            'branch_id'       => $event->branch->id,
            'name'            => $event->branch->name,
            'organization_id' => $event->branch->organization_id,
        ]);
    }
}
