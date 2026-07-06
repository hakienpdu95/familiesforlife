<?php

namespace Modules\LeadSource\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\LeadSource\Events\SourceCreated;
use Modules\LeadSource\Events\SourceDeleted;
use Modules\LeadSource\Events\SourceUpdated;

class FlushSourcesCache
{
    public function handle(SourceCreated|SourceUpdated|SourceDeleted $event): void
    {
        $orgId = match (true) {
            $event instanceof SourceDeleted => $event->organizationId ?? 0,
            default                         => $event->source->organization_id ?? 0,
        };

        $this->flush($orgId);

        if ($orgId !== 0) {
            $this->flush(0);
        }
    }

    private function flush(int $orgId): void
    {
        try {
            Cache::tags(["org:{$orgId}", 'sources'])->flush();
        } catch (\BadMethodCallException) {
            Cache::forget("lead_sources:{$orgId}");
            Cache::forget("lead_sources:{$orgId}:all");
        }
    }
}
