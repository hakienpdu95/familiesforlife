<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentResult;

class ResultClassification extends Model
{
    protected $table = 'result_classifications';

    protected $fillable = [
        'result_id',
        'classification_type',
        'band_code',
        'passed',
        'persona_code',
        'match_score',
    ];

    protected function casts(): array
    {
        return [
            'passed'      => 'boolean',
            'match_score' => 'float',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'result_id');
    }
}
