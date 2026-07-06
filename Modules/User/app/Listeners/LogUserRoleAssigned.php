<?php

namespace Modules\User\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\User\Events\UserRoleAssigned;

class LogUserRoleAssigned
{
    public function handle(UserRoleAssigned $event): void
    {
        ActivityLogger::info('User', 'user_role_assigned', $event->user, [
            'role' => $event->role,
        ]);
    }
}
