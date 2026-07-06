<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlueprintCapability extends Model
{
    protected $table = 'blueprint_capabilities';

    protected $fillable = [
        'blueprint_version_id', 'outcome_id', 'code', 'name', 'description',
        'capability_type', 'sort_order', 'status',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class, 'blueprint_version_id');
    }

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(BlueprintOutcome::class, 'outcome_id');
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(BlueprintWorkflow::class, 'capability_id')->orderBy('sort_order');
    }
}
