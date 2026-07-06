<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentConfigSnapshot;

class SnapshotRecommendationRule extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_recommendations';

    protected $fillable = [
        'snapshot_id', 'recommendation_code', 'label', 'description',
        'trigger_domain', 'threshold_score', 'priority',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AssessmentConfigSnapshot::class, 'snapshot_id');
    }
}
