<?php

namespace Modules\Ocop\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Post\Models\PostArticle;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.4 — sản phẩm OCOP, tài sản nền tảng
 * (không organization_id). province_code/ward_code KHÔNG FK cứng — cùng convention PostArticle/
 * Event (denormalize tên tại thời điểm chọn), NHƯNG khác Post ở chỗ đây LÀ địa chỉ thật của nhà
 * sản xuất (bắt buộc chọn khi tạo sản phẩm, xem §4.2), không phải gắn lỏng cho mục đích lọc.
 *
 * spec/Media_Library_Technical_Specification.md §7.5/§8 — ảnh sản phẩm qua Media (collection
 * `cover`, dùng nguyên trạng — đã chốt phù hợp vì hiển thị vuông mọi nơi), thay 4 cột phẳng
 * `image_path`/`image_width`/`image_height`/`image_size_bytes` cũ (đã xoá).
 */
class OcopProduct extends Model implements HasMedia
{
    use HasTenantMedia;
    use LogsActivity;
    use Searchable;
    use SoftDeletes;

    protected $table = 'ocop_products';

    protected $fillable = [
        'uuid', 'category_id', 'name', 'slug', 'star_rating', 'description',
        'province_code', 'province_name', 'ward_code', 'ward_name',
        'producer_name', 'producer_address', 'heritage_site_id',
        'purchase_url', 'status', 'is_featured', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'star_rating' => 'integer',
        'status' => OcopProductStatus::class,
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(OcopCategory::class, 'category_id');
    }

    /** §3.4.1 — bài viết Post nhắc tới sản phẩm này (many-to-many, tuỳ chọn). */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(PostArticle::class, 'post_article_ocop_products', 'ocop_product_id', 'article_id');
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

    /** §5 — chỉ hiển thị công khai khi status=published. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', OcopProductStatus::Published);
    }

    public function scopeForProvince(Builder $query, string $provinceCode): void
    {
        $query->where('province_code', $provinceCode);
    }

    public function scopeForWard(Builder $query, string $wardCode): void
    {
        $query->where('ward_code', $wardCode);
    }

    // ── Scout / Meilisearch (spec/SiteSearch_Activation_Expansion_Technical_Specification.md §5) ──

    /**
     * Tường minh, tự áp `config('scout.prefix')` — cùng lý do PostArticleTranslation::searchableAs():
     * MeilisearchEngine dùng thẳng giá trị trả về, không tự cộng prefix lần nữa.
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'ocop_products';
    }

    /** Chỉ đẩy vào Meilisearch sản phẩm đang published — SoftDeletes tự gọi unsearchable() khi bị xoá. */
    public function shouldBeSearchable(): bool
    {
        return $this->status === OcopProductStatus::Published;
    }

    /**
     * Payload đẩy lên Meilisearch. Khác PostArticleTranslation: category là belongsTo đơn (không
     * phải many-to-many), nên chỉ 1 tên/slug, không phải mảng.
     *
     * `loadMissing()` bắt buộc — đường đồng bộ 1 record (Create/Update/DeleteOcopProductAction
     * gọi thẳng `create()`/`update()`/`delete()` trên model, KHÔNG qua `makeAllSearchableUsing()`)
     * không tự eager-load `category`. `Model::shouldBeStrict()` (app/Providers/AppServiceProvider.php)
     * chặn lazy-load ở non-production — nếu thiếu dòng này, mọi lần tạo/sửa 1 sản phẩm ở
     * local/staging sẽ ném `LazyLoadingViolationException` ngay khi Scout gọi hàm này (phát hiện
     * qua test tự động, không phải suy đoán).
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing('category');

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => (string) $this->description,
            'producer_name' => (string) $this->producer_name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'star_rating' => $this->star_rating,
            'is_featured' => (bool) $this->is_featured,
            'province_code' => $this->province_code,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'category_slug' => $this->category?->slug,
        ];
    }

    /** Chỉ ảnh hưởng đường `scout:import`/`makeAllSearchable()` — tránh N+1 khi full-import. */
    protected function makeAllSearchableUsing($query)
    {
        return $query->with('category');
    }
}
