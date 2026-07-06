<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswer extends Model
{
    use HasFactory;

    protected $table = 'survey_answers';

    // Bảng chỉ có created_at — câu trả lời đã nộp không được sửa
    public const UPDATED_AT = null;

    protected $fillable = [
        'response_id',
        'field_id',
        'row_key',
        'option_id',
        'value_string',
        'value_text',
        'value_number',
        'value_date',
        'value_bool',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:2',
            'value_date'   => 'date',
            'value_bool'   => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'response_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(SurveyField::class, 'field_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(SurveyFieldOption::class, 'option_id');
    }
}
