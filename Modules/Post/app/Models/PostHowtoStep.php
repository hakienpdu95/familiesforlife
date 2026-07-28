<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 1 bước trong 1 PostHowtoBlock — dữ liệu tĩnh nhập tay, không tham chiếu bảng ngoài. */
class PostHowtoStep extends Model
{
    protected $table = 'post_howto_steps';

    protected $fillable = [
        'howto_block_id',
        'name',
        'text',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function howtoBlock(): BelongsTo
    {
        return $this->belongsTo(PostHowtoBlock::class, 'howto_block_id');
    }
}
