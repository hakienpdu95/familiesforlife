<?php

namespace Modules\Lead\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadTagDefinition;

class SyncLeadTagsAction
{
    use AsAction;

    public function handle(Lead $lead, array $tagIds): void
    {
        // Only allow tags that belong to the same org
        $validIds = LeadTagDefinition::where('organization_id', $lead->organization_id)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->all();

        $lead->tags()->sync($validIds);
    }
}
