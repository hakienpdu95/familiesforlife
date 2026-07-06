<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowEntityState extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'entity_type', 'state_key', 'state_label',
        'color', 'icon', 'description', 'is_initial', 'is_terminal', 'sort_order',
    ];

    protected $casts = [
        'is_initial'  => 'boolean',
        'is_terminal' => 'boolean',
        'sort_order'  => 'integer',
    ];

    public function outgoingTransitions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowEntityTransition::class, 'from_state_id');
    }

    public function incomingTransitions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowEntityTransition::class, 'to_state_id');
    }
}
