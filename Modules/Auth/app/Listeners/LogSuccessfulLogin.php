<?php

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\ActivityLog\Core\ActivityLogger;

/**
 * Ghi audit log mỗi khi user login thành công.
 * Đăng ký trong Modules\Auth\Providers\EventServiceProvider.
 */
class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        ActivityLogger::info('Auth', 'login', $event->user, [
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
            'remember'   => $event->remember,
            'method'     => session('auth.method', 'password'),
        ]);
    }
}
