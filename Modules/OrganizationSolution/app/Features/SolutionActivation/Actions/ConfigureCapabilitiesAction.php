<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\Concerns\AdvancesToConfiguring;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/** Bước 3 (spec §3.6): upsert organization_capability_configs (bulk enable/disable). */
class ConfigureCapabilitiesAction
{
    use AsAction;
    use AdvancesToConfiguring;

    /** @param array<int, array{blueprint_capability_id:int, enabled?:bool, override_name?:?string}> $items */
    public function handle(OrganizationSolution $orgSolution, array $items): void
    {
        DB::transaction(function () use ($orgSolution, $items) {
            $this->advanceToConfiguring($orgSolution);

            foreach ($items as $item) {
                $orgSolution->capabilityConfigs()->updateOrCreate(
                    ['blueprint_capability_id' => $item['blueprint_capability_id']],
                    [
                        'enabled'        => $item['enabled'] ?? true,
                        'override_name'  => $item['override_name'] ?? null,
                    ]
                );
            }
        });
    }
}
