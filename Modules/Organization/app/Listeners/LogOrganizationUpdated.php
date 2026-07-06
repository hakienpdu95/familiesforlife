<?php

namespace Modules\Organization\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Organization\Events\OrganizationUpdated;

class LogOrganizationUpdated
{
    public function handle(OrganizationUpdated $event): void
    {
        ActivityLogger::info('Organization', 'organization_updated', $event->organization, [
            'organization_id' => $event->organization->id,
            'name'            => $event->organization->name,
            'changes'         => implode(', ', array_keys($event->organization->getChanges())),
        ]);
    }
}
