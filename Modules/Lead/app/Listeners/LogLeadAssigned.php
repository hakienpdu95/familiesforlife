<?php

namespace Modules\Lead\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Lead\Events\LeadAssigned;

class LogLeadAssigned
{
    public function handle(LeadAssigned $event): void
    {
        ActivityLogger::info('Lead', 'lead_assigned', $event->lead, [
            'lead_id'         => $event->lead->id,
            'from_user_id'    => $event->fromUserId,
            'to_user_id'      => $event->toUserId,
            'organization_id' => $event->lead->organization_id,
        ]);
    }
}
