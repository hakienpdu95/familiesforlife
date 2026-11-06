<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Nhãn phẳng, không soft-delete/activity-log riêng (bảng post_tags không có deleted_at) — xoá
 * cứng khi không còn bài dùng. Platform-wide — spec/PostTag_Management_Technical_Specification.md §3.5.
 */
class PostTag extends Model
{
    protected $table = 'post_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(PostArticle::class, 'post_article_tag', 'tag_id', 'article_id');
    }
}
