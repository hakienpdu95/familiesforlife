<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class RecommendationRule extends Model
{
    use LogsActivity;
    protected $table = 'recommendation_rules';

    protected $fillable = [
        'assessment_code',
        'recommendation_code',
        'label',
        'description',
        'kc_category_id',
        'kc_item_tag',
        'career_pathway_step_code',
        'trigger_domain',
        'threshold_score',
        'priority',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('scoring');
    }

    protected function casts(): array
    {
        return [
            'threshold_score' => 'float',
            'priority'        => 'integer',
            'is_active'       => 'boolean',
        ];
    }

    public function scopeForAssessment(Builder $query, string $assessmentCode): Builder
    {
        return $query->where('assessment_code', $assessmentCode)
            ->where('is_active', true)
            ->orderBy('priority');
    }
}
