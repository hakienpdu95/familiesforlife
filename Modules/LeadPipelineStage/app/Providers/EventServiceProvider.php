<?php

namespace Modules\LeadPipelineStage\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\LeadPipelineStage\Events\StageCreated;
use Modules\LeadPipelineStage\Events\StageDeleted;
use Modules\LeadPipelineStage\Events\StageUpdated;
use Modules\LeadPipelineStage\Listeners\FlushStagesCache;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        StageCreated::class => [FlushStagesCache::class],
        StageUpdated::class => [FlushStagesCache::class],
        StageDeleted::class => [FlushStagesCache::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
