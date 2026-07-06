<?php

namespace Modules\Assessment\Providers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Assessment\Events\AssessmentCompleted;
use Modules\Assessment\Events\AssessmentFailed;
use Modules\Assessment\Events\CertificationExpired;
use Modules\Assessment\Events\CertificationIssued;
use Modules\Assessment\Events\HighDivergenceDetected;
use Modules\Assessment\Events\ImpactSnapshotRecorded;
use Modules\Assessment\Events\LowKpiAlert;
use Modules\Assessment\Events\MaturityLevelChanged;
use Modules\Assessment\Events\SandboxCompleted;
use Modules\Assessment\Listeners\LogAssessmentCompleted;
use Modules\Assessment\Listeners\SyncWorkforceProfileOnPerformanceReviewFinalizedListener;
use Modules\Assessment\Listeners\UpdateEmployeeDigitalCompetencyListener;
use Modules\Assessment\Listeners\UpdateTrustLevelOnEmailVerifiedListener;
use Modules\Assessment\Listeners\UpdateWorkforceProfileImpactScoreListener;
use Modules\Assessment\Listeners\UpdateWorkforceProfileOnAssessmentListener;
use Modules\Assessment\Listeners\UpdateWorkforceProfileOnCertificationListener;
use Modules\Assessment\Listeners\UpdateWorkforceProfileOnSandboxListener;
use Modules\PerformanceReview\Events\PerformanceReviewFinalized;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Phase 0 — email verification → trust_level + identity_verifications
        Verified::class => [
            UpdateTrustLevelOnEmailVerifiedListener::class,
        ],

        AssessmentCompleted::class => [
            LogAssessmentCompleted::class,
            UpdateEmployeeDigitalCompetencyListener::class,
            UpdateWorkforceProfileOnAssessmentListener::class,
        ],
        AssessmentFailed::class => [],
        CertificationIssued::class => [
            UpdateWorkforceProfileOnCertificationListener::class,
        ],
        CertificationExpired::class => [],
        ImpactSnapshotRecorded::class => [
            UpdateWorkforceProfileImpactScoreListener::class,
        ],
        LowKpiAlert::class => [],
        HighDivergenceDetected::class => [],
        MaturityLevelChanged::class => [],
        SandboxCompleted::class => [
            UpdateWorkforceProfileOnSandboxListener::class,
        ],
        PerformanceReviewFinalized::class => [
            SyncWorkforceProfileOnPerformanceReviewFinalizedListener::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
