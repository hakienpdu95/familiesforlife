<?php

namespace Modules\Assessment\Providers;

use Modules\Assessment\Console\Commands\AutoSuspendExpiredMembershipsCommand;
use Modules\Assessment\Console\Commands\ExpireCertificationsCommand;
use Modules\Assessment\Console\Commands\FlagInactiveMembersCommand;
use Modules\Assessment\Services\CampaignEligibility\Advisories\CrossOrgDisclosureAdvisory;
use Modules\Assessment\Services\CampaignEligibility\Guards\CampaignCapacityGuard;
use Modules\Assessment\Services\CampaignEligibility\Guards\CampaignStatusGuard;
use Modules\Assessment\Services\CampaignEligibility\Guards\MinTdwcfScoreGuard;
use Modules\Assessment\Services\CampaignEligibility\Guards\SelfOrgGuard;
use Modules\Assessment\Services\CampaignEligibility\Guards\SuspendedAccountGuard;
use Modules\Assessment\Services\CampaignEligibility\Guards\TrustLevelGuard;
use Modules\Assessment\Services\CampaignEligibilityService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AssessmentServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Assessment';
    protected string $nameLower = 'assessment';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // Campaign join eligibility pipeline.
        // Guards run in order — first block wins. Add/remove guards here to extend rules.
        $this->app->singleton(CampaignEligibilityService::class, fn() => new CampaignEligibilityService(
            guards: [
                new SuspendedAccountGuard(),
                new TrustLevelGuard(),
                new SelfOrgGuard(),
                new MinTdwcfScoreGuard(),
                new CampaignStatusGuard(),
                new CampaignCapacityGuard(),
            ],
            advisories: [
                new CrossOrgDisclosureAdvisory(),
            ],
        ));
    }

    public function boot(): void
    {
        parent::boot();
        $this->commands([
            ExpireCertificationsCommand::class,
            AutoSuspendExpiredMembershipsCommand::class,
            FlagInactiveMembersCommand::class,
        ]);
    }
}
