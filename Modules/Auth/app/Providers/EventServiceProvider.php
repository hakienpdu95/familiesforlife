<?php

namespace Modules\Auth\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Auth\Listeners\LogSuccessfulLogin;
use Modules\Auth\Listeners\SyncEmailTrustLevel;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        Verified::class => [
            SyncEmailTrustLevel::class,
        ],
    ];

    // Tắt auto-discovery — dùng $listen map tường minh để rõ ràng + hiệu suất
    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
