<?php

namespace Modules\Post\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Nhãn phẳng, không soft-delete/activity-log riêng (bảng post_tags không có deleted_at) — xoá cứng khi không còn bài dùng. */
class PostTag extends Model
{
    use BelongsToOrganization;

    protected $table = 'post_tags';

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(PostArticle::class, 'post_article_tag', 'tag_id', 'article_id');
    }
}
