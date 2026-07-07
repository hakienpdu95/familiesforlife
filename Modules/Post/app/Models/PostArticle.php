<?php

namespace Modules\Post\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Enums\ArticleStatus;
use Illuminate\Support\Str;

class PostArticle extends TenantAwareModel
{
    protected $table = 'post_articles';

    protected $fillable = [
        'uuid',
        'organization_id',
        'title',
        'slug',
        'excerpt',
        'format',
        'status',
        'cover_image_url',
        'published_at',
        'seo_title',
        'seo_description',
        'is_featured',
        'sort_order',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'format'       => ArticleFormat::class,
        'status'       => ArticleStatus::class,
        'published_at' => 'datetime',
        'approved_at'  => 'datetime',
        'view_count'   => 'integer',
        'is_featured'  => 'boolean',
        'sort_order'   => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PostCategory::class, 'post_article_categories', 'article_id', 'category_id')
            ->withPivot('is_primary');
    }

    public function primaryCategory(): BelongsToMany
    {
        return $this->categories()->wherePivot('is_primary', true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PostTag::class, 'post_article_tag', 'article_id', 'tag_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function productBlocks(): HasMany
    {
        return $this->hasMany(PostProductBlock::class, 'article_id')->orderBy('sort_order');
    }

    /** Nguồn sự thật của nội dung — dãy block (text/product) theo đúng thứ tự hiển thị. */
    public function contentBlocks(): HasMany
    {
        return $this->hasMany(PostContentBlock::class, 'article_id')->orderBy('sort_order');
    }
}
