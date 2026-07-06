<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowEntityStateLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'entity_type', 'entity_id',
        'from_state_key', 'to_state_key', 'transition_key',
        'actor_id', 'comment', 'triggered_execution_id', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];
}
