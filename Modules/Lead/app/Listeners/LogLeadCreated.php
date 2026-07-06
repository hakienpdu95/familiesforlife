<?php

namespace Modules\Lead\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Lead\Events\LeadCreated;

class LogLeadCreated
{
    public function handle(LeadCreated $event): void
    {
        ActivityLogger::info('Lead', 'lead_created', $event->lead, [
            'lead_id'         => $event->lead->id,
            'contact_name'    => $event->lead->contact_name,
            'stage_id'        => $event->lead->stage_id,
            'organization_id' => $event->lead->organization_id,
        ]);

        // Flush kanban and stats caches after creation
        $orgId = $event->lead->organization_id;
        try {
            Cache::tags(["org:{$orgId}", 'kanban'])->flush();
            Cache::tags(["org:{$orgId}", 'stats'])->flush();
        } catch (\BadMethodCallException) {
            Cache::forget("lead_kanban:{$orgId}");
        }
    }
}
