<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * GEO đợt 4 (2026-07-28) — khối "Hướng dẫn từng bước" trong content-block composer, sinh JSON-LD
 * HowTo (xem ArticleStructuredDataBuilder). Cùng quy ước PostFaqBlock: không soft-delete, không
 * tenant-scoped, không override/fallback (không tham chiếu entity ngoài).
 */
class PostHowtoBlock extends Model
{
    protected $table = 'post_howto_blocks';

    protected $fillable = [
        'uuid',
        'translation_id',
        'name',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PostArticleTranslation::class, 'translation_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(PostHowtoStep::class, 'howto_block_id')->orderBy('sort_order');
    }
}
