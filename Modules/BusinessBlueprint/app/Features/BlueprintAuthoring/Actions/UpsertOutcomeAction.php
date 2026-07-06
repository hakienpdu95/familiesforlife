<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\OutcomeData;
use Modules\BusinessBlueprint\Models\BlueprintOutcome;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class UpsertOutcomeAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(OutcomeData $data, ?BlueprintOutcome $outcome = null): BlueprintOutcome
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        $attributes = [
            'blueprint_version_id' => $data->blueprint_version_id,
            'code'                  => $data->code,
            'name'                  => $data->name,
            'description'           => $data->description,
            'success_metric'        => $data->success_metric,
            'sort_order'            => $data->sort_order,
            'status'                => $data->status,
        ];

        if (! $outcome) {
            return BlueprintOutcome::create($attributes);
        }

        $outcome->update($attributes);

        return $outcome->fresh();
    }
}
