<?php

namespace Modules\Lead\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Lead\Events\LeadStageChanged;

class LogLeadStageChanged
{
    public function handle(LeadStageChanged $event): void
    {
        ActivityLogger::info('Lead', 'lead_stage_changed', $event->lead, [
            'lead_id'         => $event->lead->id,
            'from_stage_id'   => $event->fromStageId,
            'to_stage_id'     => $event->toStageId,
            'organization_id' => $event->lead->organization_id,
        ]);

        $orgId = $event->lead->organization_id;
        try {
            Cache::tags(["org:{$orgId}", 'kanban'])->flush();
            Cache::tags(["org:{$orgId}", 'stats'])->flush();
        } catch (\BadMethodCallException) {
            Cache::forget("lead_kanban:{$orgId}");
        }
    }
}
