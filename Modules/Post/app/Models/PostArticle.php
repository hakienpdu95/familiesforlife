<?php

namespace Modules\Post\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Post\Enums\ArticleFormat;
use Illuminate\Support\Str;

class PostArticle extends TenantAwareModel
{
    protected $table = 'post_articles';

    protected $fillable = [
        'uuid',
        'organization_id',
        'main_locale',
        'format',
        'cover_image_url',
        'is_featured',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'format'      => ArticleFormat::class,
        'is_featured' => 'boolean',
        'sort_order'  => 'integer',
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

    public function translations(): HasMany
    {
        return $this->hasMany(PostArticleTranslation::class, 'article_id');
    }

    public function translation(string $locale): ?PostArticleTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }

    public function mainTranslation(): ?PostArticleTranslation
    {
        return $this->translation($this->main_locale);
    }

    /**
     * Eager-load toàn bộ translations — dùng ở ListArticlesForAdminQuery để tránh N+1 khi
     * hiển thị cột "Tiêu đề" cho mỗi dòng danh sách (qua mainTranslation()/translation()).
     * KHÔNG lọc theo main_locale ngay trong query: `whereColumn` trong closure eager-load
     * chạy trên 1 query riêng (`WHERE article_id IN (...)`) không có `post_articles` trong
     * FROM/JOIN nên không thể so sánh chéo bảng — lọc đúng locale ở tầng PHP (đã đúng theo
     * $this->translations->firstWhere() trong translation()) sau khi có đủ collection.
     */
    public function scopeWithMainTranslation(Builder $query): void
    {
        $query->with('translations');
    }
}
