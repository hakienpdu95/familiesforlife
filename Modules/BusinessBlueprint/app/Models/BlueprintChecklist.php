<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlueprintChecklist extends Model
{
    protected $table = 'blueprint_checklists';

    protected $fillable = [
        'phase_id', 'code', 'name', 'description',
        'input_description', 'action_description', 'output_description',
        'required', 'default_priority', 'estimated_hours', 'need_approval',
        'sort_order', 'status',
    ];

    protected $casts = [
        'required'        => 'boolean',
        'need_approval'   => 'boolean',
        'estimated_hours' => 'decimal:2',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(BlueprintPhase::class, 'phase_id');
    }

    public function resourceLinks(): HasMany
    {
        return $this->hasMany(BlueprintResourceLink::class, 'checklist_id');
    }

    public function aiCapabilities(): HasMany
    {
        return $this->hasMany(BlueprintAiCapability::class, 'checklist_id');
    }
}
