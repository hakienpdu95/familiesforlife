<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyFieldOption extends Model
{
    use HasFactory;

    protected $table = 'survey_field_options';

    protected $fillable = [
        'field_id',
        'option_value',
        'label',
        'sort_order',
        'is_other',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_other'   => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function field(): BelongsTo
    {
        return $this->belongsTo(SurveyField::class, 'field_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function scopeForField(Builder $query, int $fieldId): Builder
    {
        return $query->where('field_id', $fieldId);
    }
}
