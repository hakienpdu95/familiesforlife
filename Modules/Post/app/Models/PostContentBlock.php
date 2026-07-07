<?php

namespace Modules\Post\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Post\Enums\ContentBlockType;

/**
 * 1 block trong composer (text hoặc product) theo đúng thứ tự hiển thị (`sort_order`).
 * Không soft-delete — vòng đời gắn chặt bài viết, xoá cứng theo cascade.
 */
class PostContentBlock extends Model
{
    use BelongsToOrganization;

    protected $table = 'post_content_blocks';

    protected $fillable = [
        'organization_id',
        'article_id',
        'type',
        'sort_order',
        'text_html',
        'product_block_id',
    ];

    protected $casts = [
        'type'       => ContentBlockType::class,
        'sort_order' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'article_id');
    }

    public function productBlock(): BelongsTo
    {
        return $this->belongsTo(PostProductBlock::class, 'product_block_id');
    }
}
