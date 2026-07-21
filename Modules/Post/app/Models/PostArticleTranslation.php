<?php

namespace Modules\Post\Models;

use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Modules\Post\Database\Factories\PostArticleTranslationFactory;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Enums\TranslationStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §3.3 (v3.0) — không extends TenantAwareModel
 * nữa, Post không thuộc tenant/Organization nào.
 *
 * spec/Media_Library_Technical_Specification.md §5.2/§7.2 — ảnh chèn qua Jodit vào content
 * block gắn vào chính translation (collection `jodit_content`), qua
 * `UpdateTranslationAction::reassociateOrphans()`.
 */
class PostArticleTranslation extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use Searchable;
    use HasTenantMedia;

    protected $table = 'post_article_translations';

    protected static function newFactory(): Factory
    {
        return PostArticleTranslationFactory::new();
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
        'article_id',
        'locale',
        'title',
        'slug',
        'excerpt',
        'seo_title',
        'seo_description',
        'status',
        'published_at',
        'scheduled_at',
        'unpublish_reason',
        'approved_by',
        'approved_at',
        'disclosure_text',
        'cta_text',
        'cta_url',
    ];

    protected $casts = [
        'status'       => TranslationStatus::class,
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'approved_at'  => 'datetime',
        'view_count'   => 'integer',
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
     * §11.1 — route công khai `{locale}/bai-viet/{translation:slug}` cần locale làm điều
     * kiện lọc thêm (2 locale khác nhau ĐƯỢC PHÉP trùng slug, unique theo (locale, slug) —
     * không còn theo tổ chức từ v3.0) — Laravel implicit binding chỉ truyền `slug` làm giá
     * trị, không tự biết phải lọc thêm locale, nên đọc trực tiếp từ route segment `locale`
     * (đã resolve trước đó). CHỈ áp dụng khi binding qua field `slug` — route admin dùng
     * field mặc định (uuid) đi qua nhánh parent (`Model::resolveRouteBinding` mặc định của
     * Eloquent, không còn org-scoping nào để bypass từ v3.0).
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        $locale = request()->route('locale');

        return $this->where('slug', $value)
            ->where('locale', $locale)
            ->where('status', TranslationStatus::Published)
            ->first();
    }

    // ── Relationships ────────────────────────────────────────────────

    public function article(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'article_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(PostContentBlock::class, 'translation_id')->orderBy('sort_order');
    }

    public function productBlocks(): HasMany
    {
        return $this->hasMany(PostProductBlock::class, 'translation_id')->orderBy('sort_order');
    }

    public function publishingLogs(): HasMany
    {
        return $this->hasMany(PostPublishingLog::class, 'translation_id');
    }

    /** spec/Post_VersionHistory_Technical_Specification.md §8. */
    public function versions(): HasMany
    {
        return $this->hasMany(PostArticleVersion::class, 'translation_id')->orderByDesc('version_number');
    }

    public function latestVersion(): ?PostArticleVersion
    {
        return $this->versions()->first();
    }

    // ── Scopes & helpers ─────────────────────────────────────────────

    public function scopePublished($query): void
    {
        $query->where('status', TranslationStatus::Published);
    }

    /** Điều kiện đủ để bấm nút Publish — dùng để enable/disable nút ở UI lẫn guard trong PublishArticleAction. */
    public function isPublishable(): bool
    {
        return in_array($this->status, [TranslationStatus::Approved, TranslationStatus::Scheduled], true);
    }

    /** Dòng log gần nhất theo 1 action cụ thể — vd "người publish gần nhất" cho notification takedown. */
    public function latestPublishLog(): ?PostPublishingLog
    {
        return $this->publishingLogs()->where('action', 'publish')->latest('created_at')->first();
    }

    // ── Scout / Meilisearch (spec/PostSearch_Meilisearch_Technical_Specification.md) ──

    /**
     * Tên index Meilisearch — tường minh, không để Scout tự suy ra từ table name để tránh vỡ
     * khi đổi $table. PHẢI tự áp `config('scout.prefix')` ở đây — override `searchableAs()`
     * thay thế hoàn toàn implementation mặc định của trait (`config('scout.prefix').$this->
     * getTable()`), và `MeilisearchEngine` dùng thẳng giá trị trả về của hàm này, không tự
     * cộng thêm prefix lần nữa (`vendor/laravel/scout/src/Engines/MeilisearchEngine.php`)
     * — bỏ dòng `config('scout.prefix')` ở đây sẽ tạo nhầm index KHÔNG prefix, đụng tên với
     * `kc_items`/instance dùng chung khác (§5 "Lưu ý vận hành").
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'post_article_translations';
    }

    /**
     * Chỉ đẩy vào Meilisearch bản dịch ĐANG published CỦA 1 article CHƯA bị soft-delete.
     * `$this->article` đi qua BelongsTo::article() — PostArticle có SoftDeletes nên global
     * scope của quan hệ này đã tự loại record đã xoá, `$this->article` trả null nếu cha bị
     * xoá → điều kiện dưới tự đúng KHI shouldBeSearchable() được Scout gọi lại (vd translation
     * tự được save/touch lần sau). Đây là lớp phòng thủ thứ 2 — lớp thứ 1 (chính) là gọi
     * unsearchable() tường minh ở DeleteArticleAction, vì xoá article không tự đụng gì tới
     * translation nên shouldBeSearchable() không tự được Scout gọi lại ngay lúc đó.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === TranslationStatus::Published
            && $this->article !== null;
    }

    /**
     * Payload đẩy lên Meilisearch. QUERY LẠI quan hệ (contentBlocks()->get(), không phải
     * property $this->contentBlocks) — bắt buộc, vì SyncContentBlocksAction xoá-tạo-lại toàn
     * bộ post_content_blocks TRONG CÙNG transaction với $translation->update(); nếu dùng
     * property đã cache trước đó có thể dính bản cũ.
     *
     * `loadMissing()` bắt buộc — đường đồng bộ 1 record (touch-point `translations()->searchable()`
     * ở UpdateArticleAction, hay Scout tự fire qua "saved" event khi publish 1 bài) KHÔNG đi qua
     * `makeAllSearchableUsing()` (chỉ áp dụng cho `scout:import`/`makeAllSearchable()`), nên
     * `article`/`article.categories`/`article.tags` chưa chắc đã eager-load. `Model::shouldBeStrict()`
     * (app/Providers/AppServiceProvider.php) chặn lazy-load ở non-production — thiếu dòng này,
     * mọi lần publish/sửa 1 bài ở local/staging sẽ ném `LazyLoadingViolationException` ngay khi
     * Scout gọi hàm này (phát hiện qua test tự động, không phải suy đoán).
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['article.categories', 'article.tags']);

        $article = $this->article; // BelongsTo — đã eager-load ở trên, không còn lazy-load

        $bodyText = $this->contentBlocks()
            ->where('type', ContentBlockType::Text)
            ->orderBy('sort_order')
            ->pluck('text_html')
            ->map(fn ($html) => trim(strip_tags((string) $html)))
            ->filter()
            ->implode(' ');

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'locale'           => $this->locale,
            'title'            => $this->title,
            'excerpt'          => (string) $this->excerpt,
            'body_text'        => Str::limit($bodyText, 5000, ''),
            'slug'             => $this->slug,
            'status'           => $this->status->value,
            'published_at'     => $this->published_at?->timestamp,
            'article_id'       => $this->article_id,
            'format'           => $article?->format?->value,
            'is_featured'      => (bool) $article?->is_featured,
            'province_code'    => $article?->province_code,
            'category_names'   => $article?->categories->pluck('name')->all() ?? [],
            'category_slugs'   => $article?->categories->pluck('slug')->all() ?? [],
            'tag_names'        => $article?->tags->pluck('name')->all() ?? [],
        ];
    }

    /**
     * §3.1 — chỉ ảnh hưởng đường `scout:import`/`makeAllSearchable()`, KHÔNG ảnh hưởng đường
     * queue 1-record khi user publish/sửa 1 bài (đường đó đã tự eager-load đủ trong
     * toSearchableArray() vì chỉ chạy 1 lần).
     */
    protected function makeAllSearchableUsing($query)
    {
        return $query->with(['article.categories', 'article.tags']);
    }
}
