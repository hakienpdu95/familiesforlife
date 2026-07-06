<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotScoreRuleOption extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_rule_options';

    protected $fillable = [
        'snapshot_rule_id', 'option_value', 'option_label', 'score', 'signal_flag', 'sort_order',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SnapshotScoreRule::class, 'snapshot_rule_id');
    }
}
