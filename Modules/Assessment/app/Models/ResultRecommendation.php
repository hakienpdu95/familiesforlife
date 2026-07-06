<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentResult;

class ResultRecommendation extends Model
{
    protected $table = 'result_recommendations';

    protected $fillable = [
        'result_id',
        'recommendation_code',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'result_id');
    }
}
