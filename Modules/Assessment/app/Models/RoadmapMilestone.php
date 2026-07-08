<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadmapMilestone extends Model
{
    protected $table = 'roadmap_milestones';

    protected $fillable = [
        'phase_id',
        'title',
        'description',
        'sort_order',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(RoadmapPhase::class, 'phase_id');
    }
}
