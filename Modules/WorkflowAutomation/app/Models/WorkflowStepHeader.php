<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStepHeader extends Model
{
    public $timestamps = false;

    protected $fillable = ['step_id', 'header_key', 'header_value'];

    public function step(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }
}
