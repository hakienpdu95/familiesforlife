<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintResourceLink extends Model
{
    protected $table = 'blueprint_resource_links';

    protected $fillable = [
        'blueprint_version_id', 'checklist_id', 'resource_type', 'resource_id',
        'is_required', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class, 'blueprint_version_id');
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(BlueprintChecklist::class, 'checklist_id');
    }
}
