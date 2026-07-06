<?php

namespace Modules\PerformanceReview\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewCriteria extends Model
{
    protected $table = 'review_criteria';

    protected $fillable = [
        'template_id',
        'criteria_key',
        'criteria_name',
        'tdwcf_domain_code',
        'weight',
        'max_score',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'float',
        'max_score' => 'integer',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplate::class, 'template_id');
    }
}
