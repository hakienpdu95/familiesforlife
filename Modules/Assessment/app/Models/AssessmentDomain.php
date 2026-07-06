<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AssessmentDomain extends Model
{
    protected $table = 'assessment_domains';

    protected $fillable = [
        'assessment_code',
        'domain_code',
        'label',
        'weight',
        'min_score',
        'max_score',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight'    => 'float',
            'min_score' => 'integer',
            'max_score' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeForAssessment(Builder $query, string $assessmentCode): Builder
    {
        return $query->where('assessment_code', $assessmentCode)->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
