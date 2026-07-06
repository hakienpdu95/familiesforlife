<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Assessment\Models\AssessmentConfigSnapshot;

class SnapshotScoreRule extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_rules';

    protected $fillable = [
        'snapshot_id', 'field_key', 'domain_code', 'feature_code', 'signal_flag',
        'score_if_true', 'score_if_false', 'question_scoring_type', 'condition_type',
        'min_score_cap', 'max_score_cap',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AssessmentConfigSnapshot::class, 'snapshot_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(SnapshotScoreRuleOption::class, 'snapshot_rule_id')->orderBy('sort_order');
    }

    public function ranges(): HasMany
    {
        return $this->hasMany(SnapshotScoreRuleRange::class, 'snapshot_rule_id')->orderBy('sort_order');
    }
}
