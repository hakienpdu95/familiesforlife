<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Models\BlueprintVersion;
use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Features\SolutionActivation\Data\ActivateBusinessSolutionData;
use Modules\OrganizationSolution\Features\SolutionActivation\Exceptions\BlueprintVersionNotPublishedException;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Bước 1+2 (spec §3.6): nhận business_solution_id, blueprint_version_id (phải
 * status=published), tạo organization_solutions với status=draft.
 */
class ActivateBusinessSolutionAction
{
    use AsAction;

    public function handle(ActivateBusinessSolutionData $data, ?int $ownerId = null): OrganizationSolution
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);

        if ($version->status !== BlueprintVersionStatus::Published->value) {
            throw new BlueprintVersionNotPublishedException();
        }

        return OrganizationSolution::create([
            'business_solution_id' => $data->business_solution_id,
            'blueprint_version_id'  => $data->blueprint_version_id,
            'name'                  => $data->name,
            'owner_id'              => $data->owner_id ?? $ownerId ?? auth()->id(),
            'status'                => OrganizationSolutionStatus::Draft->value,
        ]);
    }
}
