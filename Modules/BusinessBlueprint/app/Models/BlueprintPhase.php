<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlueprintPhase extends Model
{
    protected $table = 'blueprint_phases';

    protected $fillable = [
        'workflow_id', 'code', 'name', 'description', 'sort_order',
        'entry_condition', 'exit_condition', 'is_initial',
        'auto_assign_data_collection', 'status',
    ];

    protected $casts = [
        'is_initial'                  => 'boolean',
        'auto_assign_data_collection' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(BlueprintWorkflow::class, 'workflow_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(BlueprintChecklist::class, 'phase_id')->orderBy('sort_order');
    }
}
