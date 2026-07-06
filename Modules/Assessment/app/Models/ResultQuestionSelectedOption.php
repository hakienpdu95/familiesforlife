<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultQuestionSelectedOption extends Model
{
    public $timestamps = false;

    protected $table = 'result_question_selected_options';

    protected $fillable = [
        'question_score_id',
        'option_key',
    ];

    public function questionScore(): BelongsTo
    {
        return $this->belongsTo(ResultQuestionScore::class, 'question_score_id');
    }
}
