<?php

namespace Modules\Branch\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Branch\Events\BranchCreated;
use Modules\Branch\Events\BranchUpdated;
use Modules\Branch\Listeners\LogBranchCreated;
use Modules\Branch\Listeners\LogBranchUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BranchCreated::class => [
            LogBranchCreated::class,
        ],
        BranchUpdated::class => [
            LogBranchUpdated::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
