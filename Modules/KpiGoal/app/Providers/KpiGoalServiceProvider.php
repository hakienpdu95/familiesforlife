<?php

namespace Modules\KpiGoal\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\KpiGoal\Models\KpiGoal;
use Modules\KpiGoal\Policies\KpiGoalPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class KpiGoalServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'KpiGoal';

    protected string $nameLower = 'kpigoal';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(KpiGoal::class, KpiGoalPolicy::class);
    }
}
