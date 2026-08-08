<?php

namespace Modules\Playlist\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Playlist\Contracts\PlaylistableContract;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Playlist_Technical_Specification.md §3/§4.1 — tài sản nền tảng (platform), không
 * organization_id, không phân cấp — cùng nguyên tắc Video/Banner/Post.
 */
class Playlist extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'slug', 'description', 'cover_image_url',
        'meta_title', 'meta_description', 'sort_order', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $playlist): void {
            $playlist->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // ── Relationships ────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Accessors ────────────────────────────────────────────────────

    /** SEO — fallback về dữ liệu hiển thị nếu chưa nhập riêng (§0/§7.3). */
    public function getEffectiveMetaTitleAttribute(): string
    {
        return $this->meta_title ?: $this->name;
    }

    public function getEffectiveMetaDescriptionAttribute(): ?string
    {
        return $this->meta_description ?: $this->description;
    }

    /**
     * Danh sách item ĐÃ LỌC hợp lệ (bỏ mồ côi/ẩn) — dùng ở MỌI nơi hiển thị công khai, KHÔNG chỉ
     * lấy $this->items thẳng ra (đó là danh sách THÔ, dùng cho trang admin để còn thấy được item
     * cần cảnh báo, §6.7). Đây là điểm hội tụ DUY NHẤT để tránh mỗi view/handler tự viết lại
     * `?->`/`isPlaylistCardVisible()` rải rác (§0/§4.2 — giảm rủi ro null-discipline).
     *
     * @return Collection<int, PlaylistableContract>
     */
    public function getVisibleItemablesAttribute(): Collection
    {
        return $this->items
            ->map(fn (PlaylistItem $item) => $item->visible_itemable)
            ->filter()
            ->values();
    }

    /**
     * Ảnh đại diện — cover_image_url tự nhập, fallback thumbnail item hợp lệ đầu tiên. Gọi ở
     * trang danh sách playlist (§7.1) — cần eager-load 'items.itemable' trước khi gọi để tránh
     * N+1 khi liệt kê nhiều playlist cùng lúc (§7.4).
     */
    public function getEffectiveCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image_url ?: $this->visible_itemables->first()?->getPlaylistCardThumbnailUrl();
    }
}
