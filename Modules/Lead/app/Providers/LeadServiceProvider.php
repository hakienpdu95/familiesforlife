<?php

namespace Modules\Lead\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadTagDefinition;
use Modules\Lead\Observers\LeadObserver;
use Modules\Lead\Policies\LeadPolicy;
use Modules\Lead\Policies\LeadTagPolicy;
use Modules\Lead\Workflow\CreateLeadExecutor;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LeadServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Lead';
    protected string $nameLower = 'lead';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path($this->name, 'config/lead.php'),
            'lead'
        );
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(LeadTagDefinition::class, LeadTagPolicy::class);

        Lead::observe(LeadObserver::class);

        if (class_exists(\Modules\WorkflowAutomation\Core\ActionRegistry::class)) {
            $this->app->booted(function () {
                app(\Modules\WorkflowAutomation\Core\ActionRegistry::class)
                    ->register(app(CreateLeadExecutor::class));
            });
        }
    }
}
