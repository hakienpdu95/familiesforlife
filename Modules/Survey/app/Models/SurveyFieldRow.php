<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyFieldRow extends Model
{
    protected $table = 'survey_field_rows';

    public $timestamps = false;

    protected $fillable = ['field_id', 'row_key', 'label', 'sort_order'];

    public function field(): BelongsTo
    {
        return $this->belongsTo(SurveyField::class, 'field_id');
    }
}
