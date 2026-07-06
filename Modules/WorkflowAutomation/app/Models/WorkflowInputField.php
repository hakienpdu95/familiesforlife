<?php

namespace Modules\WorkflowAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowInputField extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workflow_id', 'sort_order', 'field_key', 'field_label',
        'field_type', 'field_options', 'placeholder', 'default_value', 'hint', 'required',
    ];

    protected $casts = [
        'field_type'    => 'integer',
        'sort_order'    => 'integer',
        'required'      => 'boolean',
        'field_options' => 'array',
    ];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
