<?php

namespace Modules\PerformanceReview\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Models\Employee;
use Modules\PerformanceReview\Data\Requests\StorePerformanceReviewData;
use Modules\PerformanceReview\Events\PerformanceReviewCreated;
use Modules\PerformanceReview\Models\PerformanceReview;
use Modules\PerformanceReview\Models\ReviewCriteria;
use Modules\PerformanceReview\Models\ReviewScore;

class StorePerformanceReviewAction
{
    use AsAction;

    public function handle(StorePerformanceReviewData $data): PerformanceReview
    {
        $employee = Employee::withoutTenant()->find($data->employee_id);

        $review = PerformanceReview::create([
            'uuid'              => Str::uuid(),
            'organization_id'   => $data->organization_id,
            'employee_id'       => $data->employee_id,
            'reviewer_id'       => $data->reviewer_id,
            'template_id'       => $data->template_id,
            'period'            => $data->period,
            'period_start'      => $data->period_start,
            'period_end'        => $data->period_end,
            'status'            => 'draft',
            'strengths'         => $data->strengths,
            'improvements'      => $data->improvements,
            'goals_next_period' => $data->goals_next_period,
            // Copy snapshot from employee at review time
            'snap_branch_name'  => $employee?->snap_branch_name,
            'snap_dept_name'    => $employee?->snap_dept_name,
            'snap_job_title'    => $employee?->snap_job_title,
            'snap_job_level'    => $employee?->snap_job_level,
        ]);

        // Auto-create score rows from template criteria (seed with score=0)
        $criteria = ReviewCriteria::where('template_id', $data->template_id)
            ->orderBy('sort_order')
            ->get();

        // If scores are provided use them, else seed with 0
        $scoreMap = [];
        if (!empty($data->scores)) {
            foreach ($data->scores as $s) {
                $scoreMap[$s['criteria_key']] = $s;
            }
        }

        foreach ($criteria as $c) {
            $scoreEntry = $scoreMap[$c->criteria_key] ?? null;
            ReviewScore::create([
                'review_id'     => $review->id,
                'criteria_key'  => $c->criteria_key,
                'criteria_name' => $c->criteria_name,
                'score'         => $scoreEntry['score'] ?? 0,
                'weight'        => $c->weight,
                'max_score'     => $c->max_score,
                'comment'       => $scoreEntry['comment'] ?? null,
            ]);
        }

        event(new PerformanceReviewCreated($review));

        return $review;
    }
}
