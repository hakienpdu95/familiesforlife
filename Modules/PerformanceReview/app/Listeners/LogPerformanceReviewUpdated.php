<?php

namespace Modules\PerformanceReview\Listeners;

use Modules\PerformanceReview\Events\PerformanceReviewUpdated;

class LogPerformanceReviewUpdated
{
    public function handle(PerformanceReviewUpdated $event): void
    {
        activity()->on($event->review)->log('performance_review.updated');
    }
}
