<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentResult;

class ResultPainPoint extends Model
{
    protected $table = 'result_pain_points';

    protected $fillable = [
        'result_id',
        'pain_point_code',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'result_id');
    }
}
