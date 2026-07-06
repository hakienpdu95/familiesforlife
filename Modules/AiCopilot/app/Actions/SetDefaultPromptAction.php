<?php

namespace Modules\AiCopilot\Actions;

use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\AiCopilot\Models\AiPrompt;

class SetDefaultPromptAction
{
    use AsAction;

    public function handle(AiPrompt $prompt): AiPrompt
    {
        $orgId = TenantContext::getOrganizationId();

        AiPrompt::withoutTenant()
            ->where('agent_id', $prompt->agent_id)
            ->where('organization_id', $orgId)
            ->update(['is_default' => false]);

        $prompt->update(['is_default' => true]);

        ActivityLogger::info('ai_copilot', 'prompt_set_default', $prompt, [
            'prompt_name' => $prompt->name,
            'version'     => $prompt->version,
        ]);

        return $prompt->fresh();
    }
}
