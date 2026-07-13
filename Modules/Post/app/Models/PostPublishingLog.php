<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log append-only — không sửa, không soft-delete, không có updated_at.
 * Không tenant-scoped — Post là tài sản của nền tảng (spec Phase2 §3.3 v3.0).
 */
class PostPublishingLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'post_publishing_logs';

    protected $fillable = [
        'translation_id',
        'action',
        'reason',
        'performed_by',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PostArticleTranslation::class, 'translation_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }
}
