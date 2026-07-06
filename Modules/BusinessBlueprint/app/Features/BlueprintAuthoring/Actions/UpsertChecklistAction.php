<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\ChecklistData;
use Modules\BusinessBlueprint\Models\BlueprintChecklist;
use Modules\BusinessBlueprint\Models\BlueprintPhase;

class UpsertChecklistAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(ChecklistData $data, ?BlueprintChecklist $checklist = null): BlueprintChecklist
    {
        $phase = BlueprintPhase::with('workflow.version')->findOrFail($data->phase_id);
        $this->guardMutable($phase->workflow->version);

        $attributes = [
            'phase_id'             => $data->phase_id,
            'code'                  => $data->code,
            'name'                  => $data->name,
            'description'           => $data->description,
            'input_description'    => $data->input_description,
            'action_description'   => $data->action_description,
            'output_description'  => $data->output_description,
            'required'              => $data->required,
            'default_priority'      => $data->default_priority,
            'estimated_hours'       => $data->estimated_hours,
            'need_approval'         => $data->need_approval,
            'sort_order'            => $data->sort_order,
            'status'                => $data->status,
        ];

        if (! $checklist) {
            return BlueprintChecklist::create($attributes);
        }

        $checklist->update($attributes);

        return $checklist->fresh();
    }
}
