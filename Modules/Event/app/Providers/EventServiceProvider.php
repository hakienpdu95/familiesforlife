<?php

namespace Modules\Event\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Event\Jobs\ExpirePastEventsJob;
use Modules\Event\Models\Event;
use Modules\Event\Models\EventCategory;
use Modules\Event\Policies\EventCategoryPolicy;
use Modules\Event\Policies\EventPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class EventServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Event';
    protected string $nameLower = 'event';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(EventCategory::class, EventCategoryPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);

        // spec/Event_Management_Technical_Specification.md §11.1 — daily, cùng cách Post đăng
        // ký ExpireSponsoredArticlesJob (PostServiceProvider::boot()).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(new ExpirePastEventsJob(), 'low')->daily()->withoutOverlapping();
        });
    }
}
