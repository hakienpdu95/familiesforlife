<?php

namespace Modules\Assessment\Models\Snapshot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Assessment\Models\AssessmentConfigSnapshot;

class SnapshotPersona extends Model
{
    public $timestamps = false;
    protected $table = 'snapshot_personas';

    protected $fillable = [
        'snapshot_id', 'persona_code', 'label', 'description', 'sort_order',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AssessmentConfigSnapshot::class, 'snapshot_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(SnapshotPersonaCondition::class, 'snapshot_persona_id')->orderBy('sort_order');
    }
}
