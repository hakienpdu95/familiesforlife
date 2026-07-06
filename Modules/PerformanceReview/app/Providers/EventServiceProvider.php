<?php

namespace Modules\PerformanceReview\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\PerformanceReview\Events\PerformanceReviewCreated;
use Modules\PerformanceReview\Events\PerformanceReviewFinalized;
use Modules\PerformanceReview\Events\PerformanceReviewUpdated;
use Modules\PerformanceReview\Listeners\LogPerformanceReviewCreated;
use Modules\PerformanceReview\Listeners\LogPerformanceReviewUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PerformanceReviewCreated::class => [LogPerformanceReviewCreated::class],
        PerformanceReviewUpdated::class => [LogPerformanceReviewUpdated::class],
        PerformanceReviewFinalized::class => [],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
