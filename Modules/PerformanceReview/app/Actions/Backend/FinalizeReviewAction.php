<?php

namespace Modules\PerformanceReview\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PerformanceReview\Events\PerformanceReviewFinalized;
use Modules\PerformanceReview\Events\PerformanceReviewUpdated;
use Modules\PerformanceReview\Models\PerformanceReview;

class FinalizeReviewAction
{
    use AsAction;

    public function handle(PerformanceReview $review): PerformanceReview
    {
        // Calculate overall_score = SUM(score * weight / 100)
        $scores = $review->scores;
        $overallScore = $scores->sum(fn ($s) => $s->score * $s->weight / 100);

        $templateScale = $review->template?->rating_scale ?? 5;
        $maxPossible   = $scores->sum(fn ($s) => $s->max_score * $s->weight / 100);
        $pct           = $maxPossible > 0 ? ($overallScore / $maxPossible) : 0;

        $rating = match(true) {
            $pct >= 0.9  => 'excellent',
            $pct >= 0.75 => 'good',
            $pct >= 0.55 => 'average',
            $pct >= 0.4  => 'below_average',
            default      => 'poor',
        };

        $review->update([
            'overall_score'  => round($overallScore, 2),
            'overall_rating' => $rating,
            'status'         => 'finalized',
            'reviewed_at'    => now(),
        ]);

        $refreshed = $review->refresh();
        event(new PerformanceReviewUpdated($refreshed));
        event(new PerformanceReviewFinalized($refreshed));

        return $refreshed;
    }
}
