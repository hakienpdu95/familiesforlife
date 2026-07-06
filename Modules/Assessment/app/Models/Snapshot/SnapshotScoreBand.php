<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentConfigSnapshot;

class SnapshotScoreBand extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_bands';

    protected $fillable = [
        'snapshot_id', 'band_code', 'label', 'description', 'min_score', 'max_score', 'sort_order',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AssessmentConfigSnapshot::class, 'snapshot_id');
    }
}
