<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaturityLevel extends Model
{
    protected $table = 'maturity_levels';

    protected $fillable = [
        'assessment_code',
        'level_code',
        'label',
        'description',
        'min_score',
        'max_score',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'float',
            'max_score' => 'float',
        ];
    }

    public function scopeForAssessment(Builder $query, string $assessmentCode): Builder
    {
        return $query->where('assessment_code', $assessmentCode);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
