<?php

namespace Modules\Marketplace\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\JobPosting\Models\JpJobPost;
use Modules\Marketplace\Models\MktListing;
use Modules\Marketplace\Observers\JpJobPostObserver;
use Modules\Marketplace\Policies\MktListingPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MarketplaceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Marketplace';
    protected string $nameLower = 'marketplace';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(MktListing::class, MktListingPolicy::class);
        Gate::policy(\Modules\Marketplace\Models\MktApplication::class, \Modules\Marketplace\Policies\MktApplicationPolicy::class);

        // Register JP observer for marketplace sync
        if (class_exists(JpJobPost::class)) {
            JpJobPost::observe(JpJobPostObserver::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Marketplace\Console\Commands\ExpireListingsCommand::class,
            ]);
        }

        $this->callAfterResolving(\Illuminate\Console\Scheduling\Schedule::class, function ($schedule) {
            $schedule->command('marketplace:expire-listings')->hourly()->withoutOverlapping();
        });
    }
}
