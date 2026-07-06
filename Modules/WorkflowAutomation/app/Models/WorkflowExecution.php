<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\WorkflowAutomation\Enums\WorkflowStatus;

class WorkflowExecution extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workflow_id', 'organization_id', 'run_id',
        'trigger_type', 'source_module',
        'subject_type', 'subject_id', 'actor_id', 'context',
        'status', 'skip_reason', 'condition_result',
        'steps_total', 'steps_success', 'steps_failed', 'steps_scheduled',
        'steps_skipped', 'steps_halted', 'steps_waiting',
        'run_context',
        'duration_ms',
        'triggered_at', 'executed_at', 'finished_at', 'created_at',
    ];

    protected $casts = [
        'status'           => 'integer',
        'condition_result' => 'boolean',
        'context'          => 'array',
        'run_context'      => 'array',
        'triggered_at'     => 'datetime',
        'executed_at'      => 'datetime',
        'finished_at'      => 'datetime',
        'created_at'       => 'datetime',
    ];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowExecutionStep::class, 'execution_id')->orderBy('sort_order');
    }

    public function userTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowUserTask::class, 'execution_id');
    }

    public function pendingUserTask(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WorkflowUserTask::class, 'execution_id')
            ->where('status', WorkflowUserTask::STATUS_PENDING);
    }

    public function getStatusEnumAttribute(): WorkflowStatus
    {
        return WorkflowStatus::from($this->status);
    }

    public function scopeForOrganization(\Illuminate\Database\Eloquent\Builder $query, int $orgId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('organization_id', $orgId);
    }

    public function isWaiting(): bool { return $this->status === WorkflowStatus::WaitingApproval->value; }
    public function isHalted(): bool  { return $this->status === WorkflowStatus::Halted->value; }
}
