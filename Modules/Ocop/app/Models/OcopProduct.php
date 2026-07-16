<?php

namespace Modules\Ocop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Post\Models\PostArticle;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.4 — sản phẩm OCOP, tài sản nền tảng
 * (không organization_id). province_code/ward_code KHÔNG FK cứng — cùng convention PostArticle/
 * Event (denormalize tên tại thời điểm chọn), NHƯNG khác Post ở chỗ đây LÀ địa chỉ thật của nhà
 * sản xuất (bắt buộc chọn khi tạo sản phẩm, xem §4.2), không phải gắn lỏng cho mục đích lọc.
 */
class OcopProduct extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'ocop_products';

    protected $fillable = [
        'uuid', 'category_id', 'name', 'slug', 'star_rating', 'description',
        'province_code', 'province_name', 'ward_code', 'ward_name',
        'producer_name', 'producer_address',
        'image_path', 'image_width', 'image_height', 'image_size_bytes',
        'purchase_url', 'status', 'is_featured', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'star_rating'       => 'integer',
        'status'            => OcopProductStatus::class,
        'is_featured'       => 'boolean',
        'sort_order'        => 'integer',
        'image_width'       => 'integer',
        'image_height'      => 'integer',
        'image_size_bytes'  => 'integer',
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
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
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
}
