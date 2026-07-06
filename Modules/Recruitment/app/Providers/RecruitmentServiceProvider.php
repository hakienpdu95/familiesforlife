<?php

namespace Modules\Recruitment\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Recruitment\Console\Commands\ExpireOffersCommand;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcCandidate;
use Modules\Recruitment\Models\RcPipelineStage;
use Modules\Recruitment\Policies\RcApplicationPolicy;
use Modules\Recruitment\Policies\RcCandidatePolicy;
use Modules\Recruitment\Policies\RcPipelineStagePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class RecruitmentServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Recruitment';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'recruitment';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        ExpireOffersCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(RcCandidate::class, RcCandidatePolicy::class);
        Gate::policy(RcApplication::class, RcApplicationPolicy::class);
        Gate::policy(RcPipelineStage::class, RcPipelineStagePolicy::class);
    }

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('recruitment:expire-offers')->dailyAt('01:00');
    }
}
