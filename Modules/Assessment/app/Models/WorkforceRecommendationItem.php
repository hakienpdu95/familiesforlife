<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkforceRecommendationItem extends Model
{
    protected $table = 'workforce_recommendation_items';

    public $timestamps = false;

    protected $fillable = [
        'workforce_recommendation_id',
        'priority',
        'domain_code',
        'action_description',
        'resource_type',
        'resource_name',
        'resource_url',
        'estimated_duration_hours',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'priority'                   => 'integer',
            'estimated_duration_hours'   => 'float',
            'created_at'                 => 'datetime',
        ];
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(WorkforceRecommendation::class, 'workforce_recommendation_id');
    }
}
