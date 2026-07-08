<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\AiCapabilityData;
use Modules\BusinessBlueprint\Models\BlueprintAiCapability;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class UpsertAiCapabilityAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(AiCapabilityData $data, ?BlueprintAiCapability $aiCapability = null): BlueprintAiCapability
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        $attributes = [
            'blueprint_version_id' => $data->blueprint_version_id,
            'checklist_id'          => $data->checklist_id,
            'capability_code'       => $data->capability_code,
            'name'                  => $data->name,
            'description'           => $data->description,
            'ai_agent_id'           => $data->ai_agent_id,
            'ai_prompt_id'          => $data->ai_prompt_id,
            'trigger_event'         => $data->trigger_event,
            'status'                => $data->status,
        ];

        if (! $aiCapability) {
            return BlueprintAiCapability::create($attributes);
        }

        $aiCapability->update($attributes);

        return $aiCapability->fresh();
    }
}
