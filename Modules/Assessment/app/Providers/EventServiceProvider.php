<?php

namespace Modules\Assessment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Assessment\Events\AssessmentCompleted;
use Modules\Assessment\Events\AssessmentFailed;
use Modules\Assessment\Events\HighDivergenceDetected;
use Modules\Assessment\Events\MaturityLevelChanged;
use Modules\Assessment\Listeners\LogAssessmentCompleted;
use Modules\Assessment\Listeners\UpdateEmployeeDigitalCompetencyListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AssessmentCompleted::class => [
            LogAssessmentCompleted::class,
            UpdateEmployeeDigitalCompetencyListener::class,
        ],
        AssessmentFailed::class => [],
        HighDivergenceDetected::class => [],
        MaturityLevelChanged::class => [],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
