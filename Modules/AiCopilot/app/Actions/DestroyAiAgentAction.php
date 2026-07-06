<?php

namespace Modules\AiCopilot\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\AiCopilot\Models\AiAgent;

class DestroyAiAgentAction
{
    use AsAction;

    public function handle(AiAgent $agent): string
    {
        if ($agent->is_system) {
            throw new \RuntimeException('System agents cannot be deleted.');
        }

        $name = $agent->name;
        $slug = $agent->slug;

        ActivityLogger::info('ai_copilot', 'agent_deleted', null, [
            'agent_name' => $name,
            'agent_slug' => $slug,
        ]);

        $agent->delete();

        return $name;
    }
}
