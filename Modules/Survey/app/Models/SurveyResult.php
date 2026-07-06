<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SurveyResult extends Model
{
    protected $table = 'survey_results';

    protected $fillable = [
        'response_id',
        'overall_score',
        'maturity_level',
        'assessment_code',
        'weight_version',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_score'  => 'float',
            'weight_version' => 'integer',
            'calculated_at'  => 'datetime',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'response_id');
    }

    public function domainScores(): HasMany
    {
        return $this->hasMany(ResultDomainScore::class, 'result_id');
    }

    public function signalFlags(): HasMany
    {
        return $this->hasMany(ResultSignalFlag::class, 'result_id');
    }

    public function painPoints(): HasMany
    {
        return $this->hasMany(ResultPainPoint::class, 'result_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(ResultRecommendation::class, 'result_id')->orderBy('priority');
    }

    public function roadmapPhases(): HasMany
    {
        return $this->hasMany(ResultRoadmapPhase::class, 'result_id')->orderBy('sort_order');
    }

    public function questionScores(): HasMany
    {
        return $this->hasMany(ResultQuestionScore::class, 'result_id');
    }

    public function classification(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ResultClassification::class, 'result_id');
    }

    public function scopeForResponse(Builder $query, int $responseId): Builder
    {
        return $query->where('response_id', $responseId);
    }
}
