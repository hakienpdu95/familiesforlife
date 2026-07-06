<?php

namespace Modules\Lead\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\Lead\Events\TagCreated;
use Modules\Lead\Events\TagDeleted;
use Modules\Lead\Events\TagUpdated;

class FlushTagsCache
{
    public function handle(TagCreated|TagUpdated|TagDeleted $event): void
    {
        $orgId = match (true) {
            $event instanceof TagDeleted => $event->organizationId,
            default                      => $event->tag->organization_id,
        };

        $this->flush($orgId);
    }

    private function flush(int $orgId): void
    {
        try {
            Cache::tags(["org:{$orgId}", 'lead_tags'])->flush();
        } catch (\BadMethodCallException) {
            Cache::forget("lead_tags:{$orgId}");
        }
    }
}
