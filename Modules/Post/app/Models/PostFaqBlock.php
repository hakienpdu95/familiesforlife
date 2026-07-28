<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * AEO (2026-07-28) — khối "Câu hỏi thường gặp" trong content-block composer, sinh JSON-LD
 * FAQPage (xem ArticleStructuredDataBuilder). Không soft-delete — vòng đời gắn chặt bản dịch
 * bài viết, xoá cứng theo cascade (cùng quy ước PostProductBlock). Không tenant-scoped — Post
 * là tài sản của nền tảng (spec Phase2 §3.3 v3.0). Đơn giản hơn PostProductBlock (không có
 * override/fallback vì không tham chiếu entity ngoài, không có buttons/CTA, không có template).
 */
class PostFaqBlock extends Model
{
    protected $table = 'post_faq_blocks';

    protected $fillable = [
        'uuid',
        'translation_id',
        'heading',
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

    public function items(): HasMany
    {
        return $this->hasMany(PostFaqItem::class, 'faq_block_id')->orderBy('sort_order');
    }
}
