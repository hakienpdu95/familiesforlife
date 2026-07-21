<?php

namespace Modules\ContentBrief\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Enums\BriefVersionTrigger;

/**
 * spec/ContentBrief_Technical_Specification.md §3.4 — model RIÊNG, KHÔNG extend
 * TenantAwareModel dù có organization_id: đây là bảng audit trail append-only, không cần
 * global scope tự động lọc theo tenant (lọc tường minh bằng where('content_brief_id', ...)
 * hoặc where('organization_id', ...) tại nơi gọi là đủ). KHÔNG soft delete — bất biến.
 */
class ContentBriefVersion extends Model
{
    /** Nguồn duy nhất khi stamp `schema_version` vào snapshot (§0/§3.5). Tăng thủ công khi
     *  BriefSnapshotData đổi cấu trúc không tương thích ngược. */
    public const CURRENT_SCHEMA_VERSION = '1.0';

    protected $fillable = [
        'uuid', 'content_brief_id', 'organization_id', 'version_number', 'status',
        'snapshot', 'content_hash', 'trigger', 'restored_from_version_id',
        'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'rejected_reason',
        'created_by',
    ];

    protected $casts = [
        'status'         => BriefVersionStatus::class,
        'trigger'        => BriefVersionTrigger::class,
        'snapshot'       => 'array',
        'version_number' => 'integer',
        'submitted_at'   => 'datetime',
        'approved_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function contentBrief(): BelongsTo
    {
        return $this->belongsTo(ContentBrief::class);
    }

    public function restoredFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_from_version_id');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(ContentBriefGeneration::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
