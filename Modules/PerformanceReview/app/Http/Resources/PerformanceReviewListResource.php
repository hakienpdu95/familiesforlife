<?php

namespace Modules\PerformanceReview\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceReviewListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $rating = $this->overall_rating;

        return [
            'id'   => $this->id,
            'uuid' => $this->uuid,

            'employee_name'   => $this->employee?->full_name,
            'employee_code'   => $this->employee?->employee_code,
            'employee_dept'   => $this->employee?->snap_dept_name,
            'employee_branch' => $this->employee?->snap_branch_name,

            'reviewer_name'  => $this->reviewer?->full_name,
            'reviewer_code'  => $this->reviewer?->employee_code,

            'template_name'  => $this->template?->name,

            'period'         => $this->period,
            'period_start'   => $this->period_start?->format('d/m/Y'),
            'period_end'     => $this->period_end?->format('d/m/Y'),

            'status'       => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'overall_score'  => $this->overall_score,
            'overall_rating' => $rating?->value,
            'overall_rating_label' => $rating?->label(),
            'overall_rating_badge' => $rating?->badgeClass(),

            'snap_job_title' => $this->snap_job_title,
            'snap_dept_name' => $this->snap_dept_name,

            'reviewed_at'    => $this->reviewed_at?->format('d/m/Y'),
            'created_at'     => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.performance-reviews.show', $this->resource),
            'edit_url'   => route('backend.performance-reviews.edit', $this->resource),
            'delete_url' => route('backend.performance-reviews.destroy', $this->resource),
        ];
    }
}
