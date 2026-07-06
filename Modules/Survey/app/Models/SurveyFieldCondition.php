<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyFieldCondition extends Model
{
    protected $fillable = [
        'field_id',
        'depends_on_field_id',
        'operator',
        'trigger_value',
        'action',
        'sort_order',
    ];

    protected $casts = [
        'trigger_value' => 'array',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(SurveyField::class, 'field_id');
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(SurveyField::class, 'depends_on_field_id');
    }
}
