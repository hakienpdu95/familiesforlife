<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\Concerns\AdvancesToConfiguring;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Bước 5 (spec §3.6): chỉ thay reference, VD "BM-01 → BM-01-HTX" — ghi vào
 * organization_resource_overrides (thay JSON config_key='resource_override').
 */
class ConfigureResourcesAction
{
    use AsAction;
    use AdvancesToConfiguring;

    /** @param array<int, array{blueprint_resource_link_id:int, override_reference:string}> $items */
    public function handle(OrganizationSolution $orgSolution, array $items): void
    {
        DB::transaction(function () use ($orgSolution, $items) {
            $this->advanceToConfiguring($orgSolution);

            foreach ($items as $item) {
                $orgSolution->resourceOverrides()->updateOrCreate(
                    ['blueprint_resource_link_id' => $item['blueprint_resource_link_id']],
                    ['override_reference' => $item['override_reference']]
                );
            }
        });
    }
}
