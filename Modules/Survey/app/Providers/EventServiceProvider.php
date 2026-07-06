<?php

namespace Modules\Survey\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Assessment\Events\AssessmentCompleted;
use Modules\Survey\Listeners\DispatchSurveyWebhookOnAssessmentCompleted;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AssessmentCompleted::class => [
            DispatchSurveyWebhookOnAssessmentCompleted::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
