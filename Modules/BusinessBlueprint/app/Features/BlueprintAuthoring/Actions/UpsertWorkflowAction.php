<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\WorkflowData;
use Modules\BusinessBlueprint\Models\BlueprintVersion;
use Modules\BusinessBlueprint\Models\BlueprintWorkflow;

class UpsertWorkflowAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(WorkflowData $data, ?BlueprintWorkflow $workflow = null): BlueprintWorkflow
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        $attributes = [
            'blueprint_version_id' => $data->blueprint_version_id,
            'capability_id'         => $data->capability_id,
            'code'                  => $data->code,
            'name'                  => $data->name,
            'description'           => $data->description,
            'sort_order'            => $data->sort_order,
            'status'                => $data->status,
        ];

        if (! $workflow) {
            return BlueprintWorkflow::create($attributes);
        }

        $workflow->update($attributes);

        return $workflow->fresh();
    }
}
