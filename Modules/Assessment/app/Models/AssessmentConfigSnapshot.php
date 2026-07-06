<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Assessment\Models\Snapshot\SnapshotDomain;
use Modules\Assessment\Models\Snapshot\SnapshotScoreBand;
use Modules\Assessment\Models\Snapshot\SnapshotScoreRule;
use Modules\Assessment\Models\Snapshot\SnapshotPersona;
use Modules\Assessment\Models\Snapshot\SnapshotPainPointRule;
use Modules\Assessment\Models\Snapshot\SnapshotRecommendationRule;
use Modules\Assessment\Models\Snapshot\SnapshotRoadmapPhase;

class AssessmentConfigSnapshot extends Model
{
    protected $fillable = [
        'assessment_code',
        'version',
        'has_scoring',
        'aggregation_model',
        'classification_type',
        'passing_score',
        'label_pass',
        'label_fail',
        'created_by',
        'change_note',
    ];

    protected $casts = [
        'has_scoring'   => 'boolean',
        'passing_score' => 'decimal:2',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(SnapshotDomain::class, 'snapshot_id');
    }

    public function bands(): HasMany
    {
        return $this->hasMany(SnapshotScoreBand::class, 'snapshot_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(SnapshotScoreRule::class, 'snapshot_id');
    }

    public function personas(): HasMany
    {
        return $this->hasMany(SnapshotPersona::class, 'snapshot_id');
    }

    public function painPoints(): HasMany
    {
        return $this->hasMany(SnapshotPainPointRule::class, 'snapshot_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(SnapshotRecommendationRule::class, 'snapshot_id');
    }

    public function roadmapPhases(): HasMany
    {
        return $this->hasMany(SnapshotRoadmapPhase::class, 'snapshot_id');
    }
}
