<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\InterviewStatus;
use Modules\Recruitment\Enums\InterviewType;

class RcInterview extends Model
{
    protected $table = 'rc_interviews';

    protected $fillable = [
        'uuid',
        'application_id',
        'stage_id',
        'interview_type',
        'title',
        'scheduled_at',
        'duration_minutes',
        'location',
        'meeting_url',
        'meeting_id',
        'status',
        'interviewer_note',
        'created_by',
    ];

    protected $casts = [
        'interview_type' => InterviewType::class,
        'status'         => InterviewStatus::class,
        'scheduled_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(RcApplication::class, 'application_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(RcPipelineStage::class, 'stage_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function panelists(): HasMany
    {
        return $this->hasMany(RcInterviewPanelist::class, 'interview_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RcInterviewEvaluation::class, 'interview_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status?->value, ['completed', 'cancelled', 'no_show']);
    }
}
