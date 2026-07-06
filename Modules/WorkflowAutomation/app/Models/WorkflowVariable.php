<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowVariable extends Model
{
    public $timestamps = false;

    protected $fillable = ['workflow_id', 'var_key', 'var_value', 'var_type', 'description', 'is_secret'];

    protected $casts = ['is_secret' => 'boolean', 'var_type' => 'integer'];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
