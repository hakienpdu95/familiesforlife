<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowCondition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workflow_id', 'sort_order',
        'field', 'operator', 'value', 'value_type',
        'created_at',
    ];

    protected $casts = [
        'value_type' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
