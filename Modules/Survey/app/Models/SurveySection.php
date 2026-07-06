<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveySection extends Model
{
    use HasFactory;

    protected $table = 'survey_sections';

    protected $fillable = [
        'survey_id',
        'title',
        'icon',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(SurveyField::class, 'section_id')->orderBy('sort_order');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function scopeForSurvey(Builder $query, int $surveyId): Builder
    {
        return $query->where('survey_id', $surveyId);
    }
}
