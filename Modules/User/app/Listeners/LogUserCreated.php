<?php

namespace Modules\User\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\User\Events\UserCreated;

class LogUserCreated
{
    public function handle(UserCreated $event): void
    {
        ActivityLogger::info('User', 'user_created', $event->user, [
            'email' => $event->user->email,
        ]);
    }
}
