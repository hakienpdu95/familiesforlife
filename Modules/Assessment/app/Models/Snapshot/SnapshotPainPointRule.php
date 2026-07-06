<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentConfigSnapshot;

class SnapshotPainPointRule extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_pain_points';

    protected $fillable = [
        'snapshot_id', 'pain_point_code', 'label', 'required_flags',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AssessmentConfigSnapshot::class, 'snapshot_id');
    }
}
