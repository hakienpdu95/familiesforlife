<?php

namespace Modules\PerformanceReview\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Employee\Models\Employee;
use Modules\PerformanceReview\Enums\OverallRating;
use Modules\PerformanceReview\Enums\ReviewStatus;

class PerformanceReview extends TenantAwareModel
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'employee_id',
        'reviewer_id',
        'template_id',
        'period',
        'period_start',
        'period_end',
        'status',
        'overall_score',
        'overall_rating',
        'strengths',
        'improvements',
        'goals_next_period',
        'employee_comment',
        'snap_branch_name',
        'snap_dept_name',
        'snap_job_title',
        'snap_job_level',
        'reviewed_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'status'         => ReviewStatus::class,
        'overall_rating' => OverallRating::class,
        'overall_score'  => 'float',
        'snap_job_level' => 'integer',
        'period_start'   => 'date',
        'period_end'     => 'date',
        'reviewed_at'    => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplate::class, 'template_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ReviewScore::class, 'review_id');
    }
}
