<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AiCopilot\Models\AiAgent;
use Modules\AiCopilot\Models\AiPrompt;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\AiCapabilityData;
use Modules\BusinessBlueprint\Models\BlueprintAiCapability;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * `ai_agent_id`/`ai_prompt_id` là FK mềm sang module AiCopilot (không constrained() cứng
 * qua DB — giữ đúng triết lý Modular Monolith, spec §2.3 ghi chú kỹ thuật). Validate
 * tồn tại ở tầng Action bằng findOrFail() trước khi lưu.
 */
class UpsertAiCapabilityAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(AiCapabilityData $data, ?BlueprintAiCapability $aiCapability = null): BlueprintAiCapability
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        if ($data->ai_agent_id) {
            AiAgent::findOrFail($data->ai_agent_id);
        }
        if ($data->ai_prompt_id) {
            AiPrompt::findOrFail($data->ai_prompt_id);
        }

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
