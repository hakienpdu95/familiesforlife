<?php

namespace Modules\BusinessBlueprint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintAiCapability extends Model
{
    protected $table = 'blueprint_ai_capabilities';

    protected $fillable = [
        'blueprint_version_id', 'checklist_id', 'capability_code', 'name', 'description',
        'ai_agent_id', 'ai_prompt_id', 'trigger_event', 'status',
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
