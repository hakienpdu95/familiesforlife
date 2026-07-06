<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\RoadmapPhase;

class ResultRoadmapPhase extends Model
{
    protected $table = 'result_roadmap_phases';

    protected $fillable = [
        'result_id',
        'phase_id',
        'sort_order',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(SurveyResult::class, 'result_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(RoadmapPhase::class, 'phase_id');
    }
}
