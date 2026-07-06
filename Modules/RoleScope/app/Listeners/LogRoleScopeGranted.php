<?php

namespace Modules\RoleScope\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\RoleScope\Events\RoleScopeGranted;

class LogRoleScopeGranted
{
    public function handle(RoleScopeGranted $event): void
    {
        $scope = $event->roleScope;

        ActivityLogger::info('RoleScope', 'role_scope_granted', $scope, [
            'user_id'         => $scope->user_id,
            'role_id'         => $scope->role_id,
            'scope_branch_id' => $scope->scope_branch_id,
            'scope_dept_id'   => $scope->scope_dept_id,
            'expires_at'      => $scope->expires_at?->toIso8601String(),
            'organization_id' => $scope->organization_id,
        ]);
    }
}
