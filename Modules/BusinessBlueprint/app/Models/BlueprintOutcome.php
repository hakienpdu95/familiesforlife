<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlueprintOutcome extends Model
{
    protected $table = 'blueprint_outcomes';

    protected $fillable = [
        'blueprint_version_id', 'code', 'name', 'description',
        'success_metric', 'sort_order', 'status',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class, 'blueprint_version_id');
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(BlueprintCapability::class, 'outcome_id')->orderBy('sort_order');
    }
}
