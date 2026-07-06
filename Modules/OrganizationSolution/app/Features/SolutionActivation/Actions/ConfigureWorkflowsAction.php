<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\Concerns\AdvancesToConfiguring;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/** Bước 4 (spec §3.6): upsert organization_workflow_configs. */
class ConfigureWorkflowsAction
{
    use AsAction;
    use AdvancesToConfiguring;

    /** @param array<int, array{blueprint_workflow_id:int, enabled?:bool, default_owner_id?:?int, sla_days?:?int}> $items */
    public function handle(OrganizationSolution $orgSolution, array $items): void
    {
        DB::transaction(function () use ($orgSolution, $items) {
            $this->advanceToConfiguring($orgSolution);

            foreach ($items as $item) {
                $orgSolution->workflowConfigs()->updateOrCreate(
                    ['blueprint_workflow_id' => $item['blueprint_workflow_id']],
                    [
                        'enabled'          => $item['enabled'] ?? true,
                        'default_owner_id' => $item['default_owner_id'] ?? null,
                        'sla_days'         => $item['sla_days'] ?? null,
                    ]
                );
            }
        });
    }
}
