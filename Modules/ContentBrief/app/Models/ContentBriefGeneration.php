<?php

namespace Modules\ContentBrief\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\ContentBrief\Enums\GenerationStatus;

/**
 * spec/ContentBrief_Technical_Specification.md §2.2.2/§6 — KHÔNG tham chiếu bất kỳ bảng nào
 * của module khác (không FK sang post_articles/post_article_translations). Đây là điểm dừng
 * cuối cùng của module: `output` là JSON đã chuẩn hoá theo GenerationOutputData.
 */
class ContentBriefGeneration extends Model
{
    protected $fillable = [
        'uuid', 'content_brief_version_id', 'organization_id', 'status',
        'output', 'error_message', 'requested_at', 'completed_at', 'created_by',
    ];

    protected $casts = [
        'status'       => GenerationStatus::class,
        'output'       => 'array',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ContentBriefVersion::class, 'content_brief_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
