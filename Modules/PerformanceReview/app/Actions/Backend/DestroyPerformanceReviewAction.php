<?php

namespace Modules\PerformanceReview\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PerformanceReview\Models\PerformanceReview;

class DestroyPerformanceReviewAction
{
    use AsAction;

    public function handle(PerformanceReview $review): string
    {
        $label = $review->employee?->full_name . ' — ' . $review->period;
        $review->delete();
        return $label;
    }
}
