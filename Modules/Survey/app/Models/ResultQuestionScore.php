<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return $this->belongsTo(SurveyResult::class, 'result_id');
    }
}
