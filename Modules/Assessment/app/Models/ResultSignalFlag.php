<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\AssessmentResult;

class ResultSignalFlag extends Model
{
    protected $table = 'result_signal_flags';

    protected $fillable = [
        'result_id',
        'flag_code',
        'flag_value',
    ];

    protected function casts(): array
    {
        return [
            'flag_value' => 'boolean',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'result_id');
    }
}
