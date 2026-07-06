<?php

namespace Modules\Organization\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Organization\Events\OrganizationCreated;

class LogOrganizationCreated
{
    public function handle(OrganizationCreated $event): void
    {
        ActivityLogger::info('Organization', 'organization_created', $event->organization, [
            'organization_id' => $event->organization->id,
            'name'            => $event->organization->name,
        ]);
    }
}
