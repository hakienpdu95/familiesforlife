<?php

namespace Modules\Lead\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Lead\Events\LeadAssigned;
use Modules\Lead\Events\LeadCreated;
use Modules\Lead\Events\LeadStageChanged;
use Modules\Lead\Events\LeadUpdated;
use Modules\Lead\Events\TagCreated;
use Modules\Lead\Events\TagDeleted;
use Modules\Lead\Events\TagUpdated;
use Modules\Lead\Listeners\ConvertLeadOnWon;
use Modules\Lead\Listeners\FlushTagsCache;
use Modules\Lead\Listeners\LogLeadAssigned;
use Modules\Lead\Listeners\LogLeadStageChanged;
use Modules\Lead\Listeners\TriggerLeadAssessment;

class EventServiceProvider extends ServiceProvider
{
    // lead_created / lead_updated / lead_deleted → LeadObserver (BaseModelObserver)
    // LogLeadCreated / LogLeadUpdated đã bỏ để tránh double log.
    // LogLeadStageChanged / LogLeadAssigned giữ lại: business events có context riêng.
    protected $listen = [
        LeadCreated::class => [
            TriggerLeadAssessment::class,
        ],
        LeadUpdated::class => [
            TriggerLeadAssessment::class,
        ],
        LeadStageChanged::class => [
            LogLeadStageChanged::class,
            ConvertLeadOnWon::class,
        ],
        LeadAssigned::class => [
            LogLeadAssigned::class,
        ],
        TagCreated::class => [
            FlushTagsCache::class,
        ],
        TagUpdated::class => [
            FlushTagsCache::class,
        ],
        TagDeleted::class => [
            FlushTagsCache::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
