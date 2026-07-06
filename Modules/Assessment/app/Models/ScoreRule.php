<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Assessment\Models\ScoreRuleNumericRange;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ScoreRule extends Model
{
    use LogsActivity;
    protected $table = 'score_rules';

    protected $fillable = [
        'assessment_code',
        'field_key',
        'feature_code',
        'domain_code',
        'signal_flag',
        'score_if_true',
        'score_if_false',
        'condition_type',
        'question_scoring_type',
        'min_score_cap',
        'max_score_cap',
        'section_id',
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
            'score_if_true'   => 'integer',
            'score_if_false'  => 'integer',
            'min_score_cap'   => 'integer',
            'max_score_cap'   => 'integer',
            'is_active'       => 'boolean',
        ];
    }

    /** Trả về question_scoring_type nếu có, ngược lại fallback về condition_type */
    public function getScoringType(): string
    {
        return $this->question_scoring_type ?? $this->condition_type ?? 'boolean';
    }

    public function getFeatureCode(): string
    {
        return $this->feature_code ?? $this->field_key;
    }

    public function options(): HasMany
    {
        return $this->hasMany(ScoreRuleOption::class, 'rule_id')->orderBy('sort_order');
    }

    public function numericRanges(): HasMany
    {
        return $this->hasMany(ScoreRuleNumericRange::class, 'rule_id')->orderBy('sort_order');
    }

    public function scopeForAssessment(Builder $query, string $assessmentCode): Builder
    {
        return $query->where('assessment_code', $assessmentCode)->where('is_active', true);
    }
}
