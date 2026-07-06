<?php

namespace Modules\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RcEvaluationCriterion extends Model
{
    protected $table = 'rc_evaluation_criteria';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'evaluation_id',
        'criterion_name',
        'score',
        'comment',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(RcInterviewEvaluation::class, 'evaluation_id');
    }
}
