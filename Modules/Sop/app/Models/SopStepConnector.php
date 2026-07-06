<?php

namespace Modules\Sop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sop\Enums\ConnectorType;

class SopStepConnector extends Model
{
    public $timestamps = false;

    protected $table = 'sop_step_connectors';

    protected $fillable = [
        'uuid',
        'sop_id',
        'from_step_id',
        'to_step_id',
        'connector_type',
        'label',
        'color_hex',
        'sort_order',
    ];

    protected $casts = [
        'connector_type' => ConnectorType::class,
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function sop(): BelongsTo
    {
        return $this->belongsTo(SopProcess::class, 'sop_id');
    }

    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(SopStep::class, 'from_step_id');
    }

    public function toStep(): BelongsTo
    {
        return $this->belongsTo(SopStep::class, 'to_step_id');
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getDisplayColorAttribute(): string
    {
        if ($this->color_hex) {
            return $this->color_hex;
        }

        return $this->connector_type instanceof ConnectorType
            ? $this->connector_type->defaultColor()
            : '#B4B2A9';
    }
}
