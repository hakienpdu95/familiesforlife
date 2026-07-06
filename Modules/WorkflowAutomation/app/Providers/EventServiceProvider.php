<?php

namespace Modules\WorkflowAutomation\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event→workflow bindings are registered dynamically from
     * config('workflow_automation.triggers') via WorkflowEventSubscriber
     * (see WorkflowAutomationServiceProvider::boot()), so nothing is hardcoded here.
     */
    protected $listen = [];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
