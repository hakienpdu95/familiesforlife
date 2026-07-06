<?php

namespace Modules\RoleScope\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\RoleScope\Data\Requests\GrantRoleScopeData;
use Modules\RoleScope\Events\RoleScopeGranted;
use Modules\RoleScope\Models\UserRoleScope;

class GrantRoleScopeAction
{
    use AsAction;

    public function handle(GrantRoleScopeData $data): UserRoleScope
    {
        $scope = UserRoleScope::create([
            'organization_id' => $data->organization_id,
            'user_id'         => $data->user_id,
            'role_id'         => $data->role_id,
            'scope_branch_id' => $data->scope_branch_id,
            'scope_dept_id'   => $data->scope_dept_id,
            'granted_by'      => auth()->id(),
            'granted_at'      => now(),
            'expires_at'      => $data->expires_at ? \Carbon\Carbon::parse($data->expires_at) : null,
            'note'            => $data->note,
        ]);

        event(new RoleScopeGranted($scope));

        return $scope;
    }
}
