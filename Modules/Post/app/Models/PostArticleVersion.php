<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Post\Enums\VersionTrigger;

/** Append-only — không sửa, không soft-delete, không có updated_at. */
class PostArticleVersion extends Model
{
    const UPDATED_AT = null;

    protected $table = 'post_article_versions';

    protected $fillable = [
        'translation_id', 'version_number', 'trigger',
        'snapshot', 'title_snapshot', 'content_hash', 'char_count', 'block_count',
        'restored_from_version_id', 'created_by',
    ];

    protected $casts = [
        'trigger'  => VersionTrigger::class,
        'snapshot' => 'array',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PostArticleTranslation::class, 'translation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /** Chỉ non-null khi trigger=restore — version nguồn đã được khôi phục (§6.1, §18.1). */
    public function restoredFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_from_version_id');
    }

    public function textBlocks(): array
    {
        return array_values(array_filter($this->snapshot['blocks'] ?? [], fn ($b) => ($b['type'] ?? null) === 'text'));
    }

    public function productBlocks(): array
    {
        return array_values(array_filter($this->snapshot['blocks'] ?? [], fn ($b) => ($b['type'] ?? null) === 'product'));
    }
}
