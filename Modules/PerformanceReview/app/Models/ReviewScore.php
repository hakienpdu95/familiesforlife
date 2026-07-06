<?php

namespace Modules\PerformanceReview\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewScore extends Model
{
    protected $fillable = [
        'review_id',
        'criteria_key',
        'criteria_name',
        'score',
        'weight',
        'max_score',
        'comment',
    ];

    protected $casts = [
        'score'     => 'float',
        'weight'    => 'float',
        'max_score' => 'integer',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'review_id');
    }
}
