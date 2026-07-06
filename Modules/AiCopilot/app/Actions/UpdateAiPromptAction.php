<?php

namespace Modules\AiCopilot\Actions;

use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\AiCopilot\Data\Requests\StoreAiPromptData;
use Modules\AiCopilot\Models\AiPrompt;

class UpdateAiPromptAction
{
    use AsAction;

    public function handle(AiPrompt $prompt, StoreAiPromptData $data): AiPrompt
    {
        $orgId = TenantContext::getOrganizationId();

        $prompt->update([
            'name'             => $data->name,
            'description'      => $data->description,
            'system_prompt'    => $data->system_prompt,
            'user_template'    => $data->user_template,
            'variables_schema' => $data->variables_schema,
            'is_active'        => $data->is_active,
        ]);

        if ($data->is_default && !$prompt->is_default) {
            AiPrompt::withoutTenant()
                ->where('agent_id', $prompt->agent_id)
                ->where('organization_id', $orgId)
                ->where('id', '!=', $prompt->id)
                ->update(['is_default' => false]);
            $prompt->update(['is_default' => true]);
        }

        ActivityLogger::info('ai_copilot', 'prompt_updated', $prompt, [
            'version' => $prompt->version,
        ]);

        return $prompt->fresh();
    }
}
