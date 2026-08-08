<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 1 cột (đối tượng được so sánh) trong 1 PostComparisonBlock — dữ liệu tĩnh nhập tay. */
class PostComparisonColumn extends Model
{
    protected $table = 'post_comparison_columns';

    protected $fillable = [
        'comparison_block_id',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function comparisonBlock(): BelongsTo
    {
        return $this->belongsTo(PostComparisonBlock::class, 'comparison_block_id');
    }
}
