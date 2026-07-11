<?php

namespace Modules\Post\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Post\Enums\TranslationStatus;

class PostArticleTranslation extends TenantAwareModel
{
    protected $table = 'post_article_translations';

    protected $fillable = [
        'uuid',
        'article_id',
        'organization_id',
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
     * kiện lọc thêm (2 locale khác nhau ĐƯỢC PHÉP trùng slug, unique theo (org, locale,
     * slug)) — Laravel implicit binding chỉ truyền `slug` làm giá trị, không tự biết phải
     * lọc thêm locale, nên đọc trực tiếp từ route segment `locale` (đã resolve trước đó).
     * CHỈ áp dụng khi binding qua field `slug` — route admin dùng field mặc định (uuid) đi
     * qua nhánh parent (BelongsToOrganization::resolveRouteBinding), không bị đụng.
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
}
