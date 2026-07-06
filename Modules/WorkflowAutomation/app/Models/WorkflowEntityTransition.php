<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowEntityTransition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'entity_type', 'transition_key', 'transition_label',
        'from_state_id', 'to_state_id', 'allowed_roles',
        'requires_comment', 'requires_confirmation',
        'triggers_workflow_id', 'sort_order',
    ];

    protected $casts = [
        'allowed_roles'         => 'array',
        'requires_comment'      => 'boolean',
        'requires_confirmation' => 'boolean',
        'sort_order'            => 'integer',
    ];

    public function fromState(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowEntityState::class, 'from_state_id');
    }

    public function toState(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowEntityState::class, 'to_state_id');
    }

    public function triggersWorkflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'triggers_workflow_id');
    }

    public function isAllowedForRole(string $role): bool
    {
        if (empty($this->allowed_roles)) return true;
        return in_array($role, $this->allowed_roles, true);
    }
}
