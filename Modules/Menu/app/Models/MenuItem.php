<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Menu\Enums\MenuLinkType;
use Modules\Post\Models\PostCategory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Menu_Navigation_Technical_Specification.md §4 — cây điều hướng độc lập với
 * PostCategory (taxonomy nội dung). Tối đa 3 cấp (depth 0/1/2), thực thi ở Action (§5.3),
 * không phải constraint DB.
 */
class MenuItem extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'menu_items';

    protected $fillable = [
        'uuid', 'location', 'parent_id', 'depth', 'label', 'icon',
        'sort_order', 'is_active', 'open_in_new_tab',
        'link_type', 'category_id', 'url', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'open_in_new_tab' => 'boolean',
        'sort_order'      => 'integer',
        'depth'           => 'integer',
        'link_type'       => MenuLinkType::class,
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Cây 3 cấp (root → children → grandchildren), active-only, dùng cho nav công khai.
     * Eager-load `category:id,slug,is_active` ở CẢ 3 cấp — thiếu ở bất kỳ cấp nào cũng vỡ
     * `resolveUrl()` (truy cập $this->category) vì lazy loading bị tắt (Model::shouldBeStrict
     * ở AppServiceProvider, mọi môi trường ngoài production).
     */
    public static function tree(string $location = 'header'): Collection
    {
        return static::active()->root()->location($location)
            ->with(['category:id,slug,is_active', 'children' => fn ($q) => $q->active()
                ->with(['category:id,slug,is_active', 'children' => fn ($q2) => $q2->active()
                    ->with('category:id,slug,is_active')
                    ->orderBy('sort_order')])
                ->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    /** URL đích thực tế — resolve theo link_type, dùng chung cho blade + admin preview. */
    public function resolveUrl(): ?string
    {
        return match ($this->link_type) {
            MenuLinkType::Category => $this->category?->is_active
                ? route('post.public.category', ['category' => $this->category->slug])
                : null,
            MenuLinkType::Url  => $this->url,
            MenuLinkType::None => null,
        };
    }

    /**
     * spec/Menu_Navigation_Technical_Specification.md §7.2.1 — rel="nofollow" CHỈ áp dụng
     * cho link_type=url trỏ ra ngoài domain hiện tại (link category luôn nội bộ, không bao
     * giờ nofollow). URL tương đối (không có host) không tính là ngoài domain.
     */
    public function isExternalUrl(): bool
    {
        if ($this->link_type !== MenuLinkType::Url || ! $this->url) {
            return false;
        }

        $host = parse_url($this->url, PHP_URL_HOST);

        return $host !== null && $host !== request()->getHost();
    }
}
