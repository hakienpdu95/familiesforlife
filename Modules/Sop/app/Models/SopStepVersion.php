<?php

namespace Modules\Sop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Sop\Enums\ChangeType;
use Modules\Sop\Enums\StepType;

// IMMUTABLE — không bao giờ UPDATE bảng này sau khi INSERT
class SopStepVersion extends Model
{
    public $timestamps = false;

    protected $table = 'sop_step_versions';

    protected $fillable = [
        'uuid',
        'sop_version_id',
        'original_step_id',
        'position',
        'title',
        'description',
        'expected_output',
        'warning_note',
        'step_type',
        'ref_sop_id',
        'ref_sop_code',
        'branch_yes_position',
        'branch_no_position',
        'duration_minutes',
        'is_mandatory',
        'change_type',
    ];

    protected $casts = [
        'step_type'    => StepType::class,
        'change_type'  => ChangeType::class,
        'is_mandatory' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function version(): BelongsTo
    {
        return $this->belongsTo(SopVersion::class, 'sop_version_id');
    }

    public function originalStep(): BelongsTo
    {
        return $this->belongsTo(SopStep::class, 'original_step_id');
    }

    public function raciVersions(): HasMany
    {
        return $this->hasMany(SopStepRaciVersion::class, 'step_version_id');
    }
}
