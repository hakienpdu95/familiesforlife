<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotScoreRuleRange extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_rule_ranges';

    protected $fillable = [
        'snapshot_rule_id', 'min_value', 'max_value', 'score', 'signal_flag', 'sort_order',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SnapshotScoreRule::class, 'snapshot_rule_id');
    }
}
