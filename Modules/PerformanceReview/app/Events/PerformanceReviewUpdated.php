<?php

namespace Modules\PerformanceReview\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\PerformanceReview\Models\PerformanceReview;

class PerformanceReviewUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PerformanceReview $review) {}
}
