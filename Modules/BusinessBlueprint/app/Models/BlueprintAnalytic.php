<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintAnalytic extends Model
{
    protected $table = 'blueprint_analytics';

    protected $fillable = [
        'blueprint_version_id', 'metric_code', 'name', 'description',
        'metric_type', 'formula', 'source_type', 'status',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class, 'blueprint_version_id');
    }
}
