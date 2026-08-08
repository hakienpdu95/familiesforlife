<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Post\Enums\ContentBlockType;

/**
 * 1 block trong composer (text hoặc product) theo đúng thứ tự hiển thị (`sort_order`).
 * Không soft-delete — vòng đời gắn chặt bản dịch bài viết, xoá cứng theo cascade.
 * Không tenant-scoped — Post là tài sản của nền tảng (spec Phase2 §3.3 v3.0).
 */
class PostContentBlock extends Model
{
    protected $table = 'post_content_blocks';

    protected $fillable = [
        'translation_id',
        'type',
        'sort_order',
        'text_html',
        'product_block_id',
        'faq_block_id',
        'citation_text',
        'citation_source_name',
        'citation_source_url',
        'howto_block_id',
        'comparison_block_id',
        'testimonial_quote',
        'testimonial_person_name',
        'testimonial_person_title',
        'testimonial_company_name',
        'testimonial_avatar_url',
        'testimonial_result_metric',
    ];

    protected $casts = [
        'type' => ContentBlockType::class,
        'sort_order' => 'integer',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PostArticleTranslation::class, 'translation_id');
    }

    public function productBlock(): BelongsTo
    {
        return $this->belongsTo(PostProductBlock::class, 'product_block_id');
    }

    public function faqBlock(): BelongsTo
    {
        return $this->belongsTo(PostFaqBlock::class, 'faq_block_id');
    }

    public function howtoBlock(): BelongsTo
    {
        return $this->belongsTo(PostHowtoBlock::class, 'howto_block_id');
    }

    public function comparisonBlock(): BelongsTo
    {
        return $this->belongsTo(PostComparisonBlock::class, 'comparison_block_id');
    }
}
