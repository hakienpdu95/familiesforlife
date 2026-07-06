<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\PanelistResponseStatus;
use Modules\Recruitment\Enums\PanelistRole;

class RcInterviewPanelist extends Model
{
    protected $table = 'rc_interview_panelists';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'interview_id',
        'user_id',
        'role',
        'response_status',
        'responded_at',
    ];

    protected $casts = [
        'role'            => PanelistRole::class,
        'response_status' => PanelistResponseStatus::class,
        'responded_at'    => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
