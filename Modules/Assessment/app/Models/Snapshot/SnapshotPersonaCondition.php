<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotPersonaCondition extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_persona_conditions';

    protected $fillable = [
        'snapshot_persona_id', 'target_type', 'target_code', 'operator',
        'threshold_value', 'flag_value', 'sort_order',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(SnapshotPersona::class, 'snapshot_persona_id');
    }
}
