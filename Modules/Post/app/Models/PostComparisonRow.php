<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 hàng (tiêu chí so sánh) trong 1 PostComparisonBlock — dữ liệu tĩnh nhập tay. `values[i]`
 * khớp với cột có sort_order=i (0-based) — độ dài mảng PHẢI bằng số cột của block, validate ở
 * SyncContentBlocksAction::validateComparisonBlocks() trước khi ghi.
 */
class PostComparisonRow extends Model
{
    protected $table = 'post_comparison_rows';

    protected $fillable = [
        'comparison_block_id',
        'label',
        'values',
        'sort_order',
    ];

    protected $casts = [
        'values' => 'array',
        'sort_order' => 'integer',
    ];

    public function comparisonBlock(): BelongsTo
    {
        return $this->belongsTo(PostComparisonBlock::class, 'comparison_block_id');
    }
}
