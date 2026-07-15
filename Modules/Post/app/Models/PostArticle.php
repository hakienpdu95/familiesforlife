<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Post\Database\Factories\PostArticleFactory;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Enums\SponsorLabel;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §3.3 (v3.0) — Post là tài sản của nền tảng,
 * không thuộc tenant/Organization nào — KHÔNG extends TenantAwareModel (không có
 * organization_id, không global-scope theo tổ chức).
 */
class PostArticle extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'post_articles';

    protected static function newFactory(): Factory
    {
        return PostArticleFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'uuid',
        'main_locale',
        'format',
        'cover_image_url',
        'is_featured',
        'sort_order',
        'province_code',
        'province_name',
        'ward_code',
        'ward_name',
        'created_by',
        'updated_by',
        'is_sponsored',
        'sponsor_name',
        'sponsor_logo_url',
        'sponsor_label',
        'campaign_code',
        'sponsored_start_date',
        'sponsored_end_date',
        'sponsored_published_at',
    ];

    protected $casts = [
        'format'                  => ArticleFormat::class,
        'is_featured'             => 'boolean',
        'sort_order'              => 'integer',
        'is_sponsored'            => 'boolean',
        'sponsor_label'           => SponsorLabel::class,
        'sponsored_start_date'    => 'date',
        'sponsored_end_date'      => 'date',
        'sponsored_published_at'  => 'datetime',
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

    /**
     * spec/Province_Showcase_Technical_Specification.md §3.4.1 — sản phẩm OCOP liên quan
     * (many-to-many, tuỳ chọn) — bảng pivot post_article_ocop_products thuộc Modules/Ocop
     * (module phụ thuộc biết về Post, không ngược lại), Post chỉ định nghĩa quan hệ đọc.
     */
    public function ocopProducts(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Ocop\Models\OcopProduct::class, 'post_article_ocop_products', 'article_id', 'ocop_product_id');
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

    // ── Sponsored Content (spec/dac-ta-ky-thuat-bai-viet-tai-tro.md) ────────────────

    /** §4.2 v1.0 — utm_params không lưu, tính động lúc cần (render CTA / redirect click). */
    public function sponsoredUtmParams(): array
    {
        return [
            'utm_source'   => 'sponsored',
            'utm_medium'   => 'article',
            'utm_campaign' => $this->campaign_code ?: $this->mainTranslation()?->slug,
        ];
    }

    /**
     * Điều kiện hiển thị badge/disclosure. Check CẢ start_date lẫn end_date tại đây — không chỉ
     * dựa vào is_sponsored bị ExpireSponsoredArticlesJob tắt, vì job chỉ chạy daily nên
     * is_sponsored có thể còn true tới ~24h SAU khi sponsored_end_date đã qua. Kiểm tra end_date
     * trực tiếp ở đây giúp trang công khai ẩn badge NGAY khi qua ngày hết hạn, không phải đợi
     * job chạy — job vẫn cần thiết để dọn is_sponsored về false cho báo cáo/danh sách lọc, nhưng
     * không còn là nguồn sự thật DUY NHẤT cho hiển thị (spec §5).
     */
    public function isCurrentlySponsored(): bool
    {
        if (! $this->is_sponsored) {
            return false;
        }

        $today = now()->toDateString();

        if ($this->sponsored_start_date && $this->sponsored_start_date->toDateString() > $today) {
            return false; // chưa tới ngày bắt đầu hiển thị label
        }

        if ($this->sponsored_end_date && $this->sponsored_end_date->toDateString() < $today) {
            return false; // đã qua ngày hết hạn — ẩn ngay, không đợi ExpireSponsoredArticlesJob
        }

        return true;
    }
}
