<?php

namespace Modules\OcopRubric\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\OcopRubric\Models\OcopScoringSession;
use Modules\OcopRubric\Policies\OcopScoringSessionPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OcopRubricServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'OcopRubric';
    protected string $nameLower = 'ocoprubric';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(OcopScoringSession::class, OcopScoringSessionPolicy::class);
    }
}
