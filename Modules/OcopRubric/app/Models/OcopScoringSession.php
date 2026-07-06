<?php

namespace Modules\OcopRubric\Models;

use App\Models\User;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * 1 lần chấm điểm (practice hoặc self_assessment) — tenant-scoped. Bất biến
 * sau khi completed: is_locked=true chặn mọi sửa đổi tiếp theo (xem các
 * Action trong Features/ScoringSession/Actions).
 *
 * KHÔNG dùng TenantAwareModel (khác OcopProduct) — bảng `ocop_scoring_sessions`
 * không có cột `deleted_at` (§7: 1 phiên đã chấm là lịch sử bất biến, không có
 * khái niệm "xoá mềm", chỉ có status=abandoned). TenantAwareModel bó cứng
 * SoftDeletes cùng BelongsToOrganization nên không dùng được ở đây — compose
 * riêng 2 trait cần thiết (tenant scoping + activity log).
 */
class OcopScoringSession extends Model
{
    use BelongsToOrganization;
    use LogsActivity;

    protected $table = 'ocop_scoring_sessions';

    protected $fillable = [
        'uuid', 'organization_id', 'ocop_product_id', 'rubric_version_id',
        'duplicated_from_session_id', 'user_id', 'employee_id', 'mode', 'status',
        'is_locked', 'score_section_a', 'score_section_b', 'score_section_c',
        'total_score', 'star_rank', 'criteria_total', 'criteria_answered',
        'duration_seconds', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'is_locked'         => 'boolean',
        'score_section_a'   => 'decimal:2',
        'score_section_b'   => 'decimal:2',
        'score_section_c'   => 'decimal:2',
        'total_score'       => 'decimal:2',
        'star_rank'         => 'integer',
        'criteria_total'    => 'integer',
        'criteria_answered' => 'integer',
        'duration_seconds'  => 'integer',
        'started_at'        => 'datetime',
        'completed_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(OcopProduct::class, 'ocop_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rubricVersion(): BelongsTo
    {
        return $this->belongsTo(OcopRubricVersion::class, 'rubric_version_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OcopScoringAnswer::class, 'session_id');
    }

    public function disqualifierFlags(): HasMany
    {
        return $this->hasMany(OcopScoringDisqualifierFlag::class, 'session_id');
    }

    public function duplicatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicated_from_session_id');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(self::class, 'duplicated_from_session_id');
    }
}
