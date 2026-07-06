<?php

namespace Modules\Sop\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Sop\Enums\VersionStatus;

class SopVersion extends Model
{
    public $timestamps = false;

    protected $table = 'sop_versions';

    protected $fillable = [
        'uuid',
        'sop_id',
        'version_number',
        'status',
        'change_summary',
        'total_steps',
        'total_duration_minutes',
        'created_by',
        'approved_by',
        'approved_at',
        'created_at',
    ];

    protected $casts = [
        'status'      => VersionStatus::class,
        'approved_at' => 'datetime',
        'created_at'  => 'datetime',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function stepVersions(): HasMany
    {
        return $this->hasMany(SopStepVersion::class, 'sop_version_id')->orderBy('position');
    }

    public function connectorVersions(): HasMany
    {
        return $this->hasMany(SopStepConnectorVersion::class, 'sop_version_id');
    }

    public function raciVersions(): HasMany
    {
        return $this->hasMany(SopStepRaciVersion::class, 'sop_version_id');
    }

    public function approvalFlows(): HasMany
    {
        return $this->hasMany(SopApprovalFlow::class, 'sop_version_id')->orderBy('step_order');
    }
}
