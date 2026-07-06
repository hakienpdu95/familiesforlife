<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AiCopilot\Models\AiAgent;
use Modules\AiCopilot\Models\AiPrompt;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\Concerns\AdvancesToConfiguring;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/** Bước 6 (spec §3.6): upsert organization_ai_configs — validate ai_agent_id/ai_prompt_id tồn tại. */
class ConfigureAiAction
{
    use AsAction;
    use AdvancesToConfiguring;

    /** @param array<int, array{ai_capability_code:string, enabled?:bool, ai_agent_id?:?int, ai_prompt_id?:?int, provider?:?string, cost_limit?:?float}> $items */
    public function handle(OrganizationSolution $orgSolution, array $items): void
    {
        DB::transaction(function () use ($orgSolution, $items) {
            $this->advanceToConfiguring($orgSolution);

            foreach ($items as $item) {
                if (! empty($item['ai_agent_id'])) {
                    AiAgent::findOrFail($item['ai_agent_id']);
                }
                if (! empty($item['ai_prompt_id'])) {
                    AiPrompt::findOrFail($item['ai_prompt_id']);
                }

                $orgSolution->aiConfigs()->updateOrCreate(
                    ['ai_capability_code' => $item['ai_capability_code']],
                    [
                        'enabled'     => $item['enabled'] ?? true,
                        'ai_agent_id' => $item['ai_agent_id'] ?? null,
                        'ai_prompt_id' => $item['ai_prompt_id'] ?? null,
                        'provider'    => $item['provider'] ?? null,
                        'cost_limit'  => $item['cost_limit'] ?? null,
                    ]
                );
            }
        });
    }
}
