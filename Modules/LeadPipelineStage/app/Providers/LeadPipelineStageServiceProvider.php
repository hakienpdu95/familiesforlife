<?php

namespace Modules\LeadPipelineStage\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;
use Modules\LeadPipelineStage\Policies\LeadPipelineStagePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LeadPipelineStageServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'LeadPipelineStage';
    protected string $nameLower = 'lead-pipeline-stage';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            module_path($this->name, 'config/lead_pipeline_stage.php'),
            'lead_pipeline_stage'
        );
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(LeadPipelineStage::class, LeadPipelineStagePolicy::class);
    }
}
