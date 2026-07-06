<?php

namespace Modules\RoleScope\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\RoleScope\Events\RoleScopeRevoked;

class LogRoleScopeRevoked
{
    public function handle(RoleScopeRevoked $event): void
    {
        ActivityLogger::info('RoleScope', 'role_scope_revoked', null, [
            'scope_id'        => $event->roleScope->id,
            'user_name'       => $event->userName,
            'role_name'       => $event->roleName,
            'organization_id' => $event->roleScope->organization_id,
        ]);
    }
}
