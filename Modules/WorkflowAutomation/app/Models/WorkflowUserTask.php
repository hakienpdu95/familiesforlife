<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowUserTask extends Model
{
    public $timestamps = false;

    const STATUS_PENDING   = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_REJECTED  = 3;
    const STATUS_EXPIRED   = 4;
    const STATUS_CANCELLED = 5;

    protected $fillable = [
        'task_token', 'execution_id', 'step_id', 'workflow_id', 'organization_id',
        'assignee_id', 'assignee_role', 'title', 'description',
        'context_snapshot', 'form_config', 'allowed_decisions',
        'due_at', 'on_timeout', 'status', 'decision', 'form_response',
        'comment', 'completed_by', 'completed_at', 'created_at',
    ];

    protected $casts = [
        'status'            => 'integer',
        'due_at'            => 'datetime',
        'completed_at'      => 'datetime',
        'created_at'        => 'datetime',
        'context_snapshot'  => 'array',
        'form_config'       => 'array',
        'form_response'     => 'array',
        'allowed_decisions' => 'array',
    ];

    public function execution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowExecution::class);
    }

    public function step(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function isPending(): bool   { return $this->status === self::STATUS_PENDING; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isExpired(): bool   { return $this->status === self::STATUS_EXPIRED; }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_at && $this->due_at->isPast();
    }
}
