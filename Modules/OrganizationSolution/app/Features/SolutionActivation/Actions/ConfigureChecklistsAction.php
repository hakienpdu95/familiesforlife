<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\Concerns\AdvancesToConfiguring;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/** Bước 4b — Checklist Configuration (spec §3.6, A07 §8): upsert organization_checklist_configs. */
class ConfigureChecklistsAction
{
    use AsAction;
    use AdvancesToConfiguring;

    /** @param array<int, array{blueprint_checklist_id:int, enabled?:bool, default_assignee_id?:?int, default_reviewer_id?:?int, due_days?:?int}> $items */
    public function handle(OrganizationSolution $orgSolution, array $items): void
    {
        DB::transaction(function () use ($orgSolution, $items) {
            $this->advanceToConfiguring($orgSolution);

            foreach ($items as $item) {
                $orgSolution->checklistConfigs()->updateOrCreate(
                    ['blueprint_checklist_id' => $item['blueprint_checklist_id']],
                    [
                        'enabled'              => $item['enabled'] ?? true,
                        'default_assignee_id'  => $item['default_assignee_id'] ?? null,
                        'default_reviewer_id'  => $item['default_reviewer_id'] ?? null,
                        'due_days'             => $item['due_days'] ?? null,
                    ]
                );
            }
        });
    }
}
