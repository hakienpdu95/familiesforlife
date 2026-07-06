<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlueprintWorkflow extends Model
{
    protected $table = 'blueprint_workflows';

    protected $fillable = [
        'blueprint_version_id', 'capability_id', 'code', 'name', 'description',
        'sort_order', 'status',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class, 'blueprint_version_id');
    }

    public function capability(): BelongsTo
    {
        return $this->belongsTo(BlueprintCapability::class, 'capability_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(BlueprintPhase::class, 'workflow_id')->orderBy('sort_order');
    }
}
