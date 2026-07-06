<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTriggerParam extends Model
{
    public $timestamps = false;

    protected $fillable = ['workflow_id', 'param_key', 'param_value', 'param_type'];

    protected $casts = ['param_type' => 'integer'];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function castValue(): mixed
    {
        return match ($this->param_type) {
            2       => (int) $this->param_value,
            3       => (float) $this->param_value,
            4       => in_array(strtolower((string) $this->param_value), ['true', '1', 'yes']),
            default => $this->param_value,
        };
    }
}
