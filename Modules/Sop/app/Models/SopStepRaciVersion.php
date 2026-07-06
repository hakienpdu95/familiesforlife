<?php

namespace Modules\Sop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// IMMUTABLE — không bao giờ UPDATE bảng này sau khi INSERT
class SopStepRaciVersion extends Model
{
    public $timestamps = false;

    protected $table = 'sop_step_raci_versions';

    protected $fillable = [
        'uuid',
        'sop_version_id',
        'step_version_id',
        'step_position',
        'assignee_type',
        'assignee_id',
        'assignee_name',
        'raci_type',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function version(): BelongsTo
    {
        return $this->belongsTo(SopVersion::class, 'sop_version_id');
    }

    public function stepVersion(): BelongsTo
    {
        return $this->belongsTo(SopStepVersion::class, 'step_version_id');
    }
}
