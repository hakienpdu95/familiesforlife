<?php

namespace Modules\PerformanceReview\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PerformanceReview\Data\Requests\UpdatePerformanceReviewData;
use Modules\PerformanceReview\Events\PerformanceReviewUpdated;
use Modules\PerformanceReview\Models\PerformanceReview;
use Modules\PerformanceReview\Models\ReviewScore;

class UpdatePerformanceReviewAction
{
    use AsAction;

    public function handle(PerformanceReview $review, UpdatePerformanceReviewData $data): PerformanceReview
    {
        $review->update([
            'reviewer_id'        => $data->reviewer_id ?? $review->reviewer_id,
            'period'             => $data->period ?? $review->period,
            'period_start'       => $data->period_start ?? $review->period_start,
            'period_end'         => $data->period_end ?? $review->period_end,
            'strengths'          => $data->strengths,
            'improvements'       => $data->improvements,
            'goals_next_period'  => $data->goals_next_period,
            'employee_comment'   => $data->employee_comment,
        ]);

        // Update scores if provided
        if (!empty($data->scores)) {
            foreach ($data->scores as $s) {
                ReviewScore::where('review_id', $review->id)
                    ->where('criteria_key', $s['criteria_key'])
                    ->update([
                        'score'   => $s['score'],
                        'comment' => $s['comment'] ?? null,
                    ]);
            }
        }

        event(new PerformanceReviewUpdated($review));

        return $review->refresh();
    }
}
