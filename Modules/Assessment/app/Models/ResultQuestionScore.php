<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultQuestionScore extends Model
{
    protected $table = 'result_question_scores';

    protected $fillable = [
        'result_id',
        'question_code',
        'feature_code',
        'raw_score',
        'final_score',
        'selected_options',
    ];

    protected function casts(): array
    {
        return [
            'raw_score'   => 'integer',
            'final_score' => 'integer',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'result_id');
    }

    public function selectedOptions(): HasMany
    {
        return $this->hasMany(ResultQuestionSelectedOption::class, 'question_score_id');
    }
}
