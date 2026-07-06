<?php

namespace Modules\Sop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sop\Enums\ConnectorType;

// IMMUTABLE — không bao giờ UPDATE bảng này sau khi INSERT
class SopStepConnectorVersion extends Model
{
    public $timestamps = false;

    protected $table = 'sop_step_connector_versions';

    protected $fillable = [
        'uuid',
        'sop_version_id',
        'from_position',
        'to_position',
        'connector_type',
        'label',
        'color_hex',
    ];

    protected $casts = [
        'connector_type' => ConnectorType::class,
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function version(): BelongsTo
    {
        return $this->belongsTo(SopVersion::class, 'sop_version_id');
    }
}
