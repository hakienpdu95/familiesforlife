<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotRoadmapMilestone extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_roadmap_milestones';

    protected $fillable = [
        'snapshot_phase_id', 'title', 'sort_order',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(SnapshotRoadmapPhase::class, 'snapshot_phase_id');
    }
}
