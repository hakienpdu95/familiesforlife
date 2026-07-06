<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreRuleOption extends Model
{
    protected $table = 'score_rule_options';

    protected $fillable = [
        'rule_id',
        'option_value',
        'option_label',
        'score',
        'signal_flag',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ScoreRule::class, 'rule_id');
    }
}
