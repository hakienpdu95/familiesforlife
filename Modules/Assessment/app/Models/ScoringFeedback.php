<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentResult;

class ScoringFeedback extends Model
{
    protected $table = 'scoring_feedback';

    protected $fillable = [
        'result_id',
        'assessment_code',
        'predicted_band',
        'actual_band',
        'predicted_score',
        'actual_score',
        'feedback_source',
        'is_processed',
        'submitted_by',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'predicted_score' => 'float',
            'actual_score'    => 'float',
            'is_processed'    => 'boolean',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'result_id');
    }

    public function scopeUnprocessed(Builder $query, string $assessmentCode): Builder
    {
        return $query->where('assessment_code', $assessmentCode)->where('is_processed', false);
    }
}
