<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\Concerns\AdvancesToConfiguring;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Bổ sung riêng (A07 §12, "rất quan trọng"): upsert organization_role_mappings —
 * ánh xạ role trừu tượng của Blueprint (field_officer, supervisor...) sang Spatie
 * role hoặc user cụ thể của tổ chức. RoleScope (user_role_scopes) KHÔNG đổi.
 */
class MapRolesAction
{
    use AsAction;
    use AdvancesToConfiguring;

    /** @param array<int, array{blueprint_role_code:string, organization_role_id?:?int, user_id?:?int, mapping_type?:string}> $items */
    public function handle(OrganizationSolution $orgSolution, array $items): void
    {
        DB::transaction(function () use ($orgSolution, $items) {
            $this->advanceToConfiguring($orgSolution);

            foreach ($items as $item) {
                $orgSolution->roleMappings()->updateOrCreate(
                    ['blueprint_role_code' => $item['blueprint_role_code']],
                    [
                        'organization_role_id' => $item['organization_role_id'] ?? null,
                        'user_id'               => $item['user_id'] ?? null,
                        'mapping_type'          => $item['mapping_type'] ?? 'role',
                    ]
                );
            }
        });
    }
}
