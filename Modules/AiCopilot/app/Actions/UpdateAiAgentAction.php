<?php

namespace Modules\AiCopilot\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\AiCopilot\Data\Requests\StoreAiAgentData;
use Modules\AiCopilot\Models\AiAgent;

class UpdateAiAgentAction
{
    use AsAction;

    public function handle(AiAgent $agent, StoreAiAgentData $data): AiAgent
    {
        $updateFields = [
            'name'            => $data->name,
            'description'     => $data->description,
            'temperature'     => $data->temperature,
            'max_tokens'      => $data->max_tokens,
            'timeout_seconds' => $data->timeout_seconds,
            'sync_mode'       => $data->sync_mode,
            'is_active'       => $data->is_active,
        ];

        // System agents: slug, provider, model, task_type are locked
        if (!$agent->is_system) {
            $updateFields['slug']      = $data->slug;
            $updateFields['provider']  = $data->provider;
            $updateFields['model']     = $data->model;
            $updateFields['task_type'] = $data->task_type;
        }

        $agent->update($updateFields);

        ActivityLogger::info('ai_copilot', 'agent_updated', $agent, [
            'slug'     => $agent->slug,
            'is_system' => $agent->is_system,
        ]);

        return $agent->fresh();
    }
}
