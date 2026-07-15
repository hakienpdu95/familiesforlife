<?php

namespace Modules\Banner\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Banner\Enums\BannerTargetType;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Banner_Management_Technical_Specification.md §3/§4 — banner là tài sản nền tảng
 * (platform), không organization_id, không phân cấp (khác MenuItem/PostCategory).
 */
class Banner extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'banners';

    protected $fillable = [
        'uuid', 'placement', 'target_type', 'target_value', 'title',
        'image_path', 'image_width', 'image_height', 'image_size_bytes', 'alt_text',
        'link_url', 'open_in_new_tab', 'badge_label',
        'start_date', 'end_date', 'sort_order', 'is_active', 'click_count',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'target_type'     => BannerTargetType::class,
        'open_in_new_tab' => 'boolean',
        'is_active'       => 'boolean',
        'start_date'      => 'date',
        'end_date'        => 'date',
        'sort_order'      => 'integer',
        'click_count'     => 'integer',
        // target_value KHÔNG cast — string phẳng (slug category khi target_type=category).
        // Chỉ cần cast 'array' nếu sau này target_value chứa JSON (ngoài phạm vi v1.1 — xem
        // spec §9), ép cast sớm sẽ vỡ khi target_value=null.
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): void
    {
        $today = now()->toDateString();

        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today));
    }

    // ── Placement / targeting ────────────────────────────────────────

    /**
     * Dùng bởi <x-frontend.banner-slot> — cùng tinh thần MenuItem::tree()/PostCategory::navTree().
     *
     * $context — dữ liệu ngữ cảnh trang đang gọi, hiện chỉ đọc key 'category_slug'. Không có
     * key này (hoặc $context rỗng) → chỉ trả banner global, không lỗi, không cần nơi gọi kiểm
     * tra trước — những placement không có ngữ cảnh category cứ gọi
     * forPlacement($placement, limit: $n) như bình thường, không đổi cách gọi.
     *
     * Ưu tiên: banner target_type='category' khớp target_value === category_slug trước, banner
     * global (target_type NULL) lấp phần còn lại của $limit — xem spec §7.5 để biết lý do thứ
     * tự này (banner theo category thường là cam kết cụ thể hơn với 1 đối tác/chiến dịch).
     *
     * @return Collection<int, self>
     */
    public static function forPlacement(string $placement, array $context = [], ?int $limit = null): Collection
    {
        $categorySlug = $context['category_slug'] ?? null;

        $targeted = collect();

        if ($categorySlug) {
            $targeted = static::active()
                ->where('placement', $placement)
                ->where('target_type', BannerTargetType::Category)
                ->where('target_value', $categorySlug)
                ->orderBy('sort_order')
                ->get();
        }
        // else ($categorySlug === null, vd forPlacement('header_ad') không truyền $context):
        // bỏ qua hẳn khối if trên, $targeted giữ nguyên collect() rỗng — 2 bước dưới tự nhiên
        // rơi vào query global lấy đủ $limit, không cần nhánh riêng cho "không có context".

        if ($limit !== null && $targeted->count() >= $limit) {
            return $targeted->take($limit)->values();
        }

        $remainingLimit = $limit !== null ? $limit - $targeted->count() : null;

        $global = static::active()
            ->where('placement', $placement)
            ->whereNull('target_type')
            ->orderBy('sort_order')
            ->when($remainingLimit, fn ($q) => $q->limit($remainingLimit))
            ->get();

        return $targeted->concat($global)->values();
    }

    public function isExternalUrl(): bool
    {
        if (! $this->link_url) {
            return false;
        }

        $host = parse_url($this->link_url, PHP_URL_HOST);

        return $host !== null && $host !== request()->getHost();
    }

    /** Nhãn hiển thị (dropdown form admin, cột "placement" ở trang danh sách). */
    public static function getPlacementLabel(string $key): ?string
    {
        return config("banner.placements.{$key}.label");
    }

    /** Gợi ý kích thước — hiển thị ở form admin, KHÔNG chặn cứng validate. */
    public static function getPlacementRecommendedSize(string $key): ?string
    {
        return config("banner.placements.{$key}.recommended_size");
    }

    /** @return string[] Danh sách key hợp lệ — dùng trong Rule::in() khi validate. */
    public static function validPlacementKeys(): array
    {
        return array_keys(config('banner.placements', []));
    }
}
