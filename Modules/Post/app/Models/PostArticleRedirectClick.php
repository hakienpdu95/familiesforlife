<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 1 dòng / lượt click vào bài viết format=redirect — xem migration create_post_article_redirect_clicks_table. */
class PostArticleRedirectClick extends Model
{
    public $timestamps = false;

    protected $table = 'post_article_redirect_clicks';

    protected $fillable = [
        'article_id',
        'referrer',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'article_id');
    }
}
