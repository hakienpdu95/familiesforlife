<?php

namespace Modules\Sop\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Sop\Enums\StepType;
use Spatie\MediaLibrary\HasMedia;

class SopStep extends Model implements HasMedia
{
    use HasTenantMedia;

    protected $table = 'sop_steps';

    protected $fillable = [
        'uuid',
        'sop_id',
        'position',
        'title',
        'description',
        'expected_output',
        'warning_note',
        'step_type',
        'ref_sop_id',
        'branch_yes_position',
        'branch_no_position',
        'duration_minutes',
        'is_mandatory',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'step_type'    => StepType::class,
        'is_mandatory' => 'boolean',
        'is_active'    => 'boolean',
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

    public function refSop(): BelongsTo
    {
        return $this->belongsTo(SopProcess::class, 'ref_sop_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function raci(): HasMany
    {
        return $this->hasMany(SopStepRaci::class, 'step_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SopStepAttachment::class, 'step_id')->orderBy('sort_order');
    }

    public function outgoingConnectors(): HasMany
    {
        return $this->hasMany(SopStepConnector::class, 'from_step_id');
    }

    public function incomingConnectors(): HasMany
    {
        return $this->hasMany(SopStepConnector::class, 'to_step_id');
    }

    public function stepVersions(): HasMany
    {
        return $this->hasMany(SopStepVersion::class, 'original_step_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
