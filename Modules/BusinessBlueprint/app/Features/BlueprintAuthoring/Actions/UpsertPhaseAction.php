<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\PhaseData;
use Modules\BusinessBlueprint\Models\BlueprintPhase;
use Modules\BusinessBlueprint\Models\BlueprintWorkflow;

class UpsertPhaseAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(PhaseData $data, ?BlueprintPhase $phase = null): BlueprintPhase
    {
        $workflow = BlueprintWorkflow::with('version')->findOrFail($data->workflow_id);
        $this->guardMutable($workflow->version);

        $attributes = [
            'workflow_id'                  => $data->workflow_id,
            'code'                          => $data->code,
            'name'                          => $data->name,
            'description'                   => $data->description,
            'sort_order'                    => $data->sort_order,
            'entry_condition'               => $data->entry_condition,
            'exit_condition'                => $data->exit_condition,
            'is_initial'                    => $data->is_initial,
            'auto_assign_data_collection'   => $data->auto_assign_data_collection,
            'status'                        => $data->status,
        ];

        if (! $phase) {
            return BlueprintPhase::create($attributes);
        }

        $phase->update($attributes);

        return $phase->fresh();
    }
}
