<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Approval\Concerns\HasApproval;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Illuminate\Support\Str;

class Product extends TenantAwareModel
{
    use HasApproval;

    protected $table = 'products';

    protected $fillable = [
        'uuid',
        'organization_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'type',
        'short_description',
        'description',
        'price',
        'price_label',
        'currency',
        'cover_image_url',
        'status',
        'shopee_url',
        'tiktok_url',
        'supplier_url',
        'supplier_homepage_url',
        'source_ref_type',
        'source_ref_id',
        'is_featured',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type'          => ProductType::class,
        'status'        => ProductStatus::class,
        'price'         => 'decimal:2',
        'is_featured'   => 'boolean',
        'view_count'    => 'integer',
        'total_cta_click_count'   => 'integer',
        'used_in_articles_count'  => 'integer',
        'sort_order'    => 'integer',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Chỉ các trường NỘI DUNG hiển thị công khai — sửa 1 trong các trường này khi Product đang
     * Approved/Published sẽ tự động bị HasApproval::bootHasApproval() chuyển về Pending và ẩn
     * khỏi cổng thông tin cho tới khi duyệt lại. KHÔNG đưa price/status/shopee_url/tiktok_url/
     * sort_order… vào đây — đó là trục vận hành/kinh doanh, đổi giá hay bật/tắt tồn kho không
     * cần (và không nên) chờ duyệt lại nội dung
     * (spec/Workflow_Approval_Technical_Specification.md §1, §2.3).
     */
    public function approvalWatchedAttributes(): array
    {
        return ['name', 'short_description', 'description', 'cover_image_url'];
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /** Nhãn giá hiển thị — ưu tiên price_label tuỳ chỉnh, fallback định dạng price. */
    public function getDisplayPriceAttribute(): ?string
    {
        if ($this->price_label) {
            return $this->price_label;
        }

        if ($this->price !== null) {
            return number_format((float) $this->price, 0, ',', '.') . ' ' . $this->currency;
        }

        return null;
    }

    /** Link "Xem & duyệt" cho dashboard xuyên-entity của Approval (§12) — dùng route uuid sẵn có. */
    public function getApprovalDashboardUrlAttribute(): string
    {
        return route('backend.products.edit', $this);
    }
}
