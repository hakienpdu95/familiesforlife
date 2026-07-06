<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreRuleNumericRange extends Model
{
    protected $table = 'score_rule_numeric_ranges';

    protected $fillable = [
        'rule_id',
        'min_value',
        'max_value',
        'score',
        'signal_flag',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_value'  => 'float',
            'max_value'  => 'float',
            'score'      => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ScoreRule::class, 'rule_id');
    }

    public function matches(float $value): bool
    {
        $aboveMin = $this->min_value === null || $value >= $this->min_value;
        $belowMax = $this->max_value === null || $value <= $this->max_value;

        return $aboveMin && $belowMax;
    }
}
