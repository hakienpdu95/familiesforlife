<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\EvaluationVerdict;

class RcInterviewEvaluation extends Model
{
    protected $table = 'rc_interview_evaluations';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'interview_id',
        'evaluator_id',
        'overall_score',
        'strengths',
        'weaknesses',
        'recommendation',
        'verdict',
        'is_submitted',
        'submitted_at',
    ];

    protected $casts = [
        'verdict'      => EvaluationVerdict::class,
        'is_submitted' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(RcInterview::class, 'interview_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RcEvaluationCriterion::class, 'evaluation_id');
    }
}
