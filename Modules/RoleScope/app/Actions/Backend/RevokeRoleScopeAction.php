<?php

namespace Modules\RoleScope\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\RoleScope\Events\RoleScopeRevoked;
use Modules\RoleScope\Models\UserRoleScope;

class RevokeRoleScopeAction
{
    use AsAction;

    public function handle(UserRoleScope $scope): array
    {
        $scope->loadMissing(['user:id,name', 'role:id,name']);

        $userName = $scope->user?->name ?? 'Unknown';
        $roleName = $scope->role?->name ?? 'Unknown';

        event(new RoleScopeRevoked($scope, $userName, $roleName));

        $scope->delete();

        return compact('userName', 'roleName');
    }
}
