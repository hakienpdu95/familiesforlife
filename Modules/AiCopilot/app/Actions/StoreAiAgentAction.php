<?php

namespace Modules\AiCopilot\Actions;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\AiCopilot\Data\Requests\StoreAiAgentData;
use Modules\AiCopilot\Models\AiAgent;

class StoreAiAgentAction
{
    use AsAction;

    public function handle(StoreAiAgentData $data): AiAgent
    {
        $orgId = $data->organization_id ?? TenantContext::getOrganizationId();

        $agent = AiAgent::create([
            'uuid'             => Str::uuid(),
            'organization_id'  => $orgId,
            'name'             => $data->name,
            'slug'             => $data->slug,
            'description'      => $data->description,
            'task_type'        => $data->task_type,
            'provider'         => $data->provider,
            'model'            => $data->model,
            'temperature'      => $data->temperature,
            'max_tokens'       => $data->max_tokens,
            'timeout_seconds'  => $data->timeout_seconds,
            'sync_mode'        => $data->sync_mode,
            'is_active'        => $data->is_active,
            'is_system'        => false,
            'created_by'       => auth()->id(),
        ]);

        ActivityLogger::info('ai_copilot', 'agent_created', $agent, [
            'slug'     => $agent->slug,
            'provider' => $agent->provider,
            'model'    => $agent->model,
        ]);

        return $agent;
    }
}
