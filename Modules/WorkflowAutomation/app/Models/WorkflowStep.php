<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\WorkflowAutomation\Enums\StepType;

class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id', 'group_id', 'sort_order',
        'step_key', 'label', 'step_type', 'action_type',
        'action_config', 'condition_config', 'step_output_key',
        'halt_on_fail', 'retry_times', 'timeout_seconds',
        // Legacy typed columns kept for backward compat
        'email_to', 'email_subject', 'email_template',
        'notif_title', 'notif_body', 'notif_target',
        'update_model', 'update_field', 'update_value',
        'webhook_url', 'webhook_method', 'webhook_secret',
        'lead_status', 'lead_source', 'lead_assigned_to',
        'user_tag', 'user_status',
        'delay_minutes',
    ];

    protected $casts = [
        'sort_order'       => 'integer',
        'step_type'        => 'integer',
        'webhook_method'   => 'integer',
        'delay_minutes'    => 'integer',
        'retry_times'      => 'integer',
        'timeout_seconds'  => 'integer',
        'halt_on_fail'     => 'boolean',
        'action_config'    => 'array',
        'condition_config' => 'array',
    ];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowStepGroup::class, 'group_id');
    }

    public function headers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowStepHeader::class, 'step_id');
    }

    public function userTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowUserTask::class, 'step_id');
    }

    public function getStepTypeEnumAttribute(): StepType
    {
        return StepType::from($this->step_type ?? StepType::Automated->value);
    }

    public function isUserTask(): bool
    {
        return ($this->step_type ?? StepType::Automated->value) === StepType::UserTask->value;
    }

    public function isControl(): bool
    {
        return ($this->step_type ?? StepType::Automated->value) === StepType::Control->value;
    }

    public function getActionConfigValue(string $key, mixed $default = null): mixed
    {
        return ($this->action_config ?? [])[$key] ?? $default;
    }
}
