<?php

namespace Modules\LeadSource\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\LeadSource\Models\LeadSource;
use Modules\LeadSource\Policies\LeadSourcePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LeadSourceServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'LeadSource';
    protected string $nameLower = 'lead-source';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path($this->name, 'config/lead_source.php'),
            'lead_source'
        );
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(LeadSource::class, LeadSourcePolicy::class);
    }
}
