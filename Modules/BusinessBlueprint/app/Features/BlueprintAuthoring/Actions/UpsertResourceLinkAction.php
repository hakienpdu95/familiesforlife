<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\ResourceLinkData;
use Modules\BusinessBlueprint\Models\BlueprintResourceLink;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class UpsertResourceLinkAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(ResourceLinkData $data, ?BlueprintResourceLink $resourceLink = null): BlueprintResourceLink
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        $attributes = [
            'blueprint_version_id' => $data->blueprint_version_id,
            'checklist_id'          => $data->checklist_id,
            'resource_type'         => $data->resource_type,
            'resource_id'           => $data->resource_id,
            'is_required'           => $data->is_required,
            'sort_order'            => $data->sort_order,
        ];

        if (! $resourceLink) {
            return BlueprintResourceLink::create($attributes);
        }

        $resourceLink->update($attributes);

        return $resourceLink->fresh();
    }
}
