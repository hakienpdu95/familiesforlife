<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\CapabilityData;
use Modules\BusinessBlueprint\Models\BlueprintCapability;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class UpsertCapabilityAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(CapabilityData $data, ?BlueprintCapability $capability = null): BlueprintCapability
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        $attributes = [
            'blueprint_version_id' => $data->blueprint_version_id,
            'outcome_id'            => $data->outcome_id,
            'code'                  => $data->code,
            'name'                  => $data->name,
            'description'           => $data->description,
            'capability_type'       => $data->capability_type,
            'sort_order'            => $data->sort_order,
            'status'                => $data->status,
        ];

        if (! $capability) {
            return BlueprintCapability::create($attributes);
        }

        $capability->update($attributes);

        return $capability->fresh();
    }
}
