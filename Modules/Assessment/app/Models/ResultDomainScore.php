<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentResult;

class ResultDomainScore extends Model
{
    protected $table = 'result_domain_scores';

    protected $fillable = [
        'result_id',
        'domain_code',
        'raw_score',
        'normalized_score',
    ];

    protected function casts(): array
    {
        return [
            'raw_score'        => 'integer',
            'normalized_score' => 'float',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'result_id');
    }
}
