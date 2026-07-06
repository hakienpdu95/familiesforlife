<?php

namespace Modules\AiCopilot\Actions;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\AiCopilot\Data\Requests\StoreAiPromptData;
use Modules\AiCopilot\Models\AiAgent;
use Modules\AiCopilot\Models\AiPrompt;

class StoreAiPromptAction
{
    use AsAction;

    public function handle(StoreAiPromptData $data): AiPrompt
    {
        $orgId = $data->organization_id ?? TenantContext::getOrganizationId();

        // Bump version: find max version for this agent in this org
        $maxVersion = AiPrompt::withoutTenant()
            ->where('agent_id', $data->agent_id)
            ->where('organization_id', $orgId)
            ->max('version') ?? 0;

        $prompt = AiPrompt::create([
            'uuid'             => Str::uuid(),
            'organization_id'  => $orgId,
            'agent_id'         => $data->agent_id,
            'name'             => $data->name,
            'description'      => $data->description,
            'system_prompt'    => $data->system_prompt,
            'user_template'    => $data->user_template,
            'variables_schema' => $data->variables_schema,
            'is_default'       => $data->is_default,
            'is_active'        => $data->is_active,
            'version'          => $maxVersion + 1,
            'created_by'       => auth()->id(),
        ]);

        if ($data->is_default) {
            // Clear other defaults for this agent in this org
            AiPrompt::withoutTenant()
                ->where('agent_id', $data->agent_id)
                ->where('organization_id', $orgId)
                ->where('id', '!=', $prompt->id)
                ->update(['is_default' => false]);
        }

        $agent = AiAgent::withoutTenant()->withoutGlobalScope('active')->find($data->agent_id);

        ActivityLogger::info('ai_copilot', 'prompt_created', $prompt, [
            'agent_slug' => $agent?->slug,
            'version'    => $prompt->version,
        ]);

        return $prompt;
    }
}
