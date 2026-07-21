<?php

namespace Modules\ContentBrief\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\ContentBrief\Enums\BriefVersionStatus;

/**
 * spec/ContentBrief_Technical_Specification.md §0/§3.4 — bản ghi định danh, tenant-scoped
 * (organization_id qua TenantAwareModel), KHÔNG chứa nội dung brief — toàn bộ nội dung nằm ở
 * ContentBriefVersion.snapshot (Document-oriented + Versioning).
 */
class ContentBrief extends TenantAwareModel
{
    protected $fillable = [
        'uuid', 'title', 'target_keyword', 'category_label', 'assigned_to',
        'status', 'current_version_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'status' => BriefVersionStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContentBriefVersion::class)->orderByDesc('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ContentBriefVersion::class, 'current_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
