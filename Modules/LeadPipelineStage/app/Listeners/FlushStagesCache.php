<?php

namespace Modules\LeadPipelineStage\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\LeadPipelineStage\Events\StageCreated;
use Modules\LeadPipelineStage\Events\StageDeleted;
use Modules\LeadPipelineStage\Events\StageUpdated;

class FlushStagesCache
{
    public function handle(StageCreated|StageUpdated|StageDeleted $event): void
    {
        $orgId = match (true) {
            $event instanceof StageDeleted  => $event->organizationId ?? 0,
            default                         => $event->stage->organization_id ?? 0,
        };

        $this->flush($orgId);

        // Also flush global (orgId=0) if this was a global stage
        if ($orgId !== 0) {
            $this->flush(0);
        }
    }

    private function flush(int $orgId): void
    {
        try {
            Cache::tags(["org:{$orgId}", 'pipeline'])->flush();
        } catch (\BadMethodCallException) {
            Cache::forget("pipeline_stages:{$orgId}");
            Cache::forget("pipeline_stages:{$orgId}:all");
        }
    }
}
