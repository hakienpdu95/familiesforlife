<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\DeploymentRoleData;
use Modules\BusinessBlueprint\Models\BlueprintDeploymentRole;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class UpsertDeploymentRoleAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(DeploymentRoleData $data, ?BlueprintDeploymentRole $role = null): BlueprintDeploymentRole
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        $attributes = [
            'blueprint_version_id' => $data->blueprint_version_id,
            'role_code'             => $data->role_code,
            'role_name'             => $data->role_name,
            'description'           => $data->description,
            'sort_order'            => $data->sort_order,
        ];

        if (! $role) {
            return BlueprintDeploymentRole::create($attributes);
        }

        $role->update($attributes);

        return $role->fresh();
    }
}
