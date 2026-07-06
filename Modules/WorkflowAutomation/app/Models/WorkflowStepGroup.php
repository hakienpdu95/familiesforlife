<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStepGroup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workflow_id', 'sort_order', 'name',
        'execute_mode', 'delay_minutes', 'halt_workflow_on_fail',
    ];

    protected $casts = [
        'sort_order'            => 'integer',
        'execute_mode'          => 'integer',
        'delay_minutes'         => 'integer',
        'halt_workflow_on_fail' => 'boolean',
    ];

    // execute_mode constants
    const MODE_SEQUENTIAL    = 1;
    const MODE_PARALLEL      = 2;
    const MODE_PARALLEL_ANY  = 3;

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'group_id')->orderBy('sort_order');
    }

    public function isParallel(): bool
    {
        return in_array($this->execute_mode, [self::MODE_PARALLEL, self::MODE_PARALLEL_ANY], true);
    }
}
