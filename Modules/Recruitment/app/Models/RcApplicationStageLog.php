<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\StageLogResult;

class RcApplicationStageLog extends Model
{
    protected $table = 'rc_application_stage_logs';

    const UPDATED_AT = null;
    const CREATED_AT = 'actioned_at';

    protected $fillable = [
        'uuid',
        'application_id',
        'stage_id',
        'result',
        'note',
        'actioned_by',
        'actioned_at',
    ];

    protected $casts = [
        'result'      => StageLogResult::class,
        'actioned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────

    public function application(): BelongsTo
    {
        return $this->belongsTo(RcApplication::class, 'application_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(RcPipelineStage::class, 'stage_id');
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }
}
