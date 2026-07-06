<?php

namespace Modules\PerformanceReview\Listeners;

use Modules\PerformanceReview\Events\PerformanceReviewCreated;

class LogPerformanceReviewCreated
{
    public function handle(PerformanceReviewCreated $event): void
    {
        activity()->on($event->review)->log('performance_review.created');
    }
}
