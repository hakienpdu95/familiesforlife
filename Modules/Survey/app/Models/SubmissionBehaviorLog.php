<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionBehaviorLog extends Model
{
    protected $table = 'submission_behavior_log';
    public $timestamps = false;

    protected $fillable = [
        'response_id',
        'question_code',
        'event_type',
        'event_value',
        'sequence_no',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'response_id');
    }

    public function scopeForResponse(Builder $query, int $responseId): Builder
    {
        return $query->where('response_id', $responseId)->orderBy('sequence_no');
    }
}
