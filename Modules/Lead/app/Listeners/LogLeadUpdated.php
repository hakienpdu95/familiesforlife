<?php

namespace Modules\Lead\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Lead\Events\LeadUpdated;

class LogLeadUpdated
{
    public function handle(LeadUpdated $event): void
    {
        ActivityLogger::info('Lead', 'lead_updated', $event->lead, [
            'lead_id'         => $event->lead->id,
            'contact_name'    => $event->lead->contact_name,
            'organization_id' => $event->lead->organization_id,
        ]);

        $orgId = $event->lead->organization_id;
        try {
            Cache::tags(["org:{$orgId}", 'stats'])->flush();
        } catch (\BadMethodCallException) {
            // File driver — TTL-based expiry
        }
    }
}
