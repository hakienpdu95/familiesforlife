<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\ValueKind;

class SurveyField extends Model
{
    use HasFactory;

    protected $table = 'survey_fields';

    protected $fillable = [
        'survey_id',
        'section_id',
        'parent_field_id',
        'field_key',
        'label',
        'field_type',
        'value_kind',
        'is_required',
        'sort_order',
        'rule_min',
        'rule_max',
        'rule_max_select',
        'placeholder',
    ];

    protected function casts(): array
    {
        return [
            'field_type'     => FieldType::class,
            'value_kind'     => ValueKind::class,
            'is_required'    => 'boolean',
            'is_active'      => 'boolean',
            'sort_order'     => 'integer',
            'rule_min'       => 'integer',
            'rule_max'       => 'integer',
            'rule_max_select' => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SurveySection::class, 'section_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SurveyField::class, 'parent_field_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SurveyField::class, 'parent_field_id')->orderBy('sort_order');
    }

    public function options(): HasMany
    {
        return $this->hasMany(SurveyFieldOption::class, 'field_id')->orderBy('sort_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class, 'field_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(SurveyFieldCondition::class, 'field_id')->orderBy('sort_order');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(SurveyFieldRow::class, 'field_id')->orderBy('sort_order');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForSurvey(Builder $query, int $surveyId): Builder
    {
        return $query->where('survey_id', $surveyId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
