<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 1 cặp câu hỏi/trả lời trong 1 PostFaqBlock — dữ liệu tĩnh nhập tay, không tham chiếu bảng ngoài. */
class PostFaqItem extends Model
{
    protected $table = 'post_faq_items';

    protected $fillable = [
        'faq_block_id',
        'question',
        'answer',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function faqBlock(): BelongsTo
    {
        return $this->belongsTo(PostFaqBlock::class, 'faq_block_id');
    }
}
