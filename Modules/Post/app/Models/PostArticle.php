<?php

namespace Modules\Post\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Ocop\Models\OcopProduct;
use Modules\Playlist\Contracts\PlaylistableContract;
use Modules\Post\Database\Factories\PostArticleFactory;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Enums\SponsorLabel;
use Modules\Post\Enums\TranslationStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §3.3 (v3.0) — Post là tài sản của nền tảng,
 * không thuộc tenant/Organization nào — KHÔNG extends TenantAwareModel (không có
 * organization_id, không global-scope theo tổ chức).
 *
 * spec/Media_Library_Technical_Specification.md §8 — cover image qua Media (collection `cover`),
 * thay cho cột `cover_image_url` cũ.
 *
 * implements PlaylistableContract — spec/Playlist_Technical_Specification.md §4.5: bài viết
 * "tham gia" làm item của Modules/Playlist qua hợp đồng này. CHỈ bản dịch published ở
 * config('post.default_locale') được phép xuất hiện trong playlist công khai (§0 — chống 404 vì
 * PublicArticleController chỉ phục vụ đúng locale mặc định).
 */
class PostArticle extends Model implements HasMedia, PlaylistableContract
{
    use HasFactory;
    use HasTenantMedia;
    use LogsActivity;
    use SoftDeletes;

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
        'redirect_url',
        'is_featured',
        'sort_order',
        'province_code',
        'ward_code',
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
        'format' => ArticleFormat::class,
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'is_sponsored' => 'boolean',
        'sponsor_label' => SponsorLabel::class,
        'sponsored_start_date' => 'date',
        'sponsored_end_date' => 'date',
        'sponsored_published_at' => 'datetime',
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

    /**
     * spec/Media_Library_Technical_Specification.md §8 — thay cho cột `cover_image_url` cũ (đã
     * xoá). Backward-compat: mọi nơi đọc `$article->cover_image_url` (article-card.blade.php,
     * hero.blade.php, hero-story.blade.php) tiếp tục hoạt động không cần sửa gì — accessor này
     * được ưu tiên trước khi Eloquent tìm cột DB cùng tên (đã không còn tồn tại).
     */
    public function getCoverImageUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('cover', 'medium');
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
        return $this->belongsToMany(OcopProduct::class, 'post_article_ocop_products', 'article_id', 'ocop_product_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostArticleTranslation::class, 'article_id');
    }

    /** format=redirect — lịch sử từng lượt click, xem RecordArticleRedirectClickAction. */
    public function redirectClicks(): HasMany
    {
        return $this->hasMany(PostArticleRedirectClick::class, 'article_id');
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
            'utm_source' => 'sponsored',
            'utm_medium' => 'article',
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
    /** format=redirect — bài không có nội dung riêng, PublicArticleController redirect thẳng ra redirect_url. */
    public function isRedirect(): bool
    {
        return $this->format === ArticleFormat::Redirect;
    }

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

    // ── PlaylistableContract (spec/Playlist_Technical_Specification.md §4.5) ───────────────

    /**
     * Bản dịch DUY NHẤT được phép dùng cho mọi output công khai xuyên-module (§0 — chống 404 vì
     * PublicArticleController chỉ phục vụ đúng locale này). Đọc từ collection `translations` —
     * khi gọi qua Playlist, collection này ĐÃ ĐƯỢC EAGER-LOAD đúng điều kiện qua
     * config('playlist.itemables.post_article.with') (§7.4), không tự query lại ở đây (tránh
     * N+1 khi hiển thị nhiều PostArticle trong 1 playlist).
     */
    public function getDefaultLocaleTranslationAttribute(): ?PostArticleTranslation
    {
        return $this->translations
            ->where('locale', config('post.default_locale'))
            ->firstWhere('status', TranslationStatus::Published);
    }

    public function getPlaylistCardTitle(): string
    {
        return $this->default_locale_translation?->title ?? '';
    }

    public function getPlaylistCardDescription(): ?string
    {
        return $this->default_locale_translation?->excerpt;
    }

    public function getPlaylistCardThumbnailUrl(): ?string
    {
        return $this->cover_image_url ?: null;
    }

    public function getPlaylistCardUrl(): string
    {
        $translation = $this->default_locale_translation;

        return $translation
            ? route('post.public.article', ['slug' => $translation->slug, 'id' => $this->id])
            : '#';
    }

    /** Bài viết luôn điều hướng, không mở lightbox (§0) — khác Video. */
    public function getPlaylistCardEmbedUrl(): ?string
    {
        return null;
    }

    public function getPlaylistCardTypeLabel(): string
    {
        return 'Bài viết';
    }

    public function isPlaylistCardVisible(): bool
    {
        return $this->default_locale_translation !== null;
    }

    public function scopeSearchablePlaylistItems(Builder $query, string $keyword): void
    {
        $query->whereHas('translations', fn ($q) => $q
            ->where('locale', config('post.default_locale'))
            ->where('status', TranslationStatus::Published)
            ->where('title', 'like', "%{$keyword}%"));
    }
}
