<?php

namespace Modules\Post\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Breaking_News_Ticker_Technical_Specification.md §3/§4 — "đánh dấu nóng" 1 PostArticle
 * đã published, tài sản nền tảng (không organization_id, cùng nguyên tắc Banner/Post §3.3 v3.0).
 */
class PostBreakingNews extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'post_breaking_news';

    protected $fillable = [
        'uuid', 'article_id', 'headline_override', 'badge_label',
        'starts_at', 'ends_at', 'sort_order', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'sort_order'  => 'integer',
        'is_active'   => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

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

    public function article(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'article_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    /**
     * Cùng cấu trúc Banner::scopeActive() (Modules/Banner/app/Models/Banner.php:90-97) nhưng
     * so sánh theo DATETIME (now()), không phải toDateString() — §0 "Độ chính xác lịch hiển thị".
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /**
     * Cùng tinh thần PostArticle::isCurrentlySponsored() — check tại thời điểm render, KHÔNG
     * phụ thuộc job dọn dẹp nào (§0 "Job dọn dẹp định kỳ": Breaking News không có job).
     */
    public function isCurrentlyBreaking(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    /**
     * Danh sách tin đang active, đã sẵn sàng render (eager-load article+category, loại bài đã
     * bị xoá mềm hoặc không còn bản dịch PUBLISHED ĐÚNG LOCALE CÔNG KHAI). Dùng bởi cả
     * PublicCategoryController::index() (render lần đầu) VÀ endpoint polling JSON §7.3 (cùng 1
     * nguồn sự thật, tránh 2 nơi query khác nhau lệch kết quả).
     *
     * Lọc theo `config('post.default_locale')` (KHÔNG dùng `article->main_locale` — 2 giá trị
     * này có thể khác nhau, còn route công khai `post.public.article` chỉ tồn tại cho bản dịch
     * đúng `default_locale`, xem PublicArticleController::show()). Vì vậy publicTranslation()
     * bên dưới cũng KHÔNG dùng PostArticle::mainTranslation() (tra theo main_locale).
     *
     * @return Collection<int, self>
     */
    public static function currentList(int $limit): Collection
    {
        $locale = (string) config('post.default_locale');

        return static::active()
            ->whereHas('article', fn ($q) => $q->whereHas(
                'translations',
                fn ($t) => $t->published()->where('locale', $locale)
            ))
            ->with([
                'article.categories',
                'article.translations' => fn ($t) => $t->published()->where('locale', $locale),
            ])
            ->orderBy('sort_order')
            ->orderByDesc('starts_at')
            ->limit($limit)
            ->get();
    }

    /** Bản dịch published đúng locale công khai — dùng để build URL/tiêu đề, xem currentList(). */
    public function publicTranslation(): ?PostArticleTranslation
    {
        return $this->article?->translation((string) config('post.default_locale'));
    }

    /** Tiêu đề hiển thị trên ticker — ưu tiên override, fallback title thật của bài. */
    public function displayHeadline(): string
    {
        return $this->headline_override ?: (string) $this->publicTranslation()?->title;
    }

    /** Nhãn badge — ưu tiên tuỳ chỉnh, fallback config mặc định. */
    public function displayBadgeLabel(): string
    {
        return $this->badge_label ?: (string) config('post.breaking_news.default_badge_label', 'NÓNG');
    }
}
