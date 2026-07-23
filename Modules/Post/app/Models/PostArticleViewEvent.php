<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Event log insert-only — KHÔNG soft-delete, KHÔNG updated_at (chỉ ghi, không sửa). Tự dọn qua
 * PruneArticleViewEventsJob theo config('post.related_posts.behavior_lookback_days') (§6.2).
 */
class PostArticleViewEvent extends Model
{
    protected $table = 'post_article_view_events';

    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'visitor_hash',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'article_id');
    }
}
