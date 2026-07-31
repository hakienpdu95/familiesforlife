<?php

namespace Modules\RealEstate\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Shared\Tenancy\OrganizationScope;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Modules\Approval\Concerns\HasApproval;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\RealEstate\Enums\ApartmentSubtype;
use Modules\RealEstate\Enums\CompassDirection;
use Modules\RealEstate\Enums\HouseSubtype;
use Modules\RealEstate\Enums\InteriorStatus;
use Modules\RealEstate\Enums\LegalStatus;
use Modules\RealEstate\Enums\ListingType;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\UsageStatus;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/RealEstateForSale_Technical_Specification.md §4.1 — 1 bảng dùng chung cho cả bán/thuê
 * (listing_type phân biệt, §0). Tenant-scoped (Organization đăng tin) + workflow duyệt qua
 * Modules\Approval\Concerns\HasApproval (xuyên-domain, KHÔNG viết lại state machine riêng).
 */
class RealEstateListing extends TenantAwareModel implements HasMedia
{
    use HasApproval;
    use HasTenantMedia;
    use Searchable;

    protected $table = 'real_estate_listings';

    protected $fillable = [
        'organization_id', 'listing_type', 'property_type', 'title', 'slug', 'description',
        'address_detail', 'province_code', 'ward_code', 'area', 'bedrooms', 'bathrooms',
        'floors', 'interior_status', 'is_price_negotiable', 'price', 'is_urgent', 'urgent_days',
        'monthly_rent', 'deposit', 'rental_period_months',
        // Field đặc thù theo property_type — cột SQL thật, KHÔNG dùng JSON (§0 v1.2)
        'house_subtype', 'apartment_subtype', 'width', 'length', 'land_area', 'usable_area',
        'net_area', 'legal_status', 'direction', 'balcony_direction', 'project_name',
        'apartment_address', 'usage_status', 'front_road_width', 'current_rental_income',
        'management_fee',
        'is_featured', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'listing_type'          => ListingType::class,
        'property_type'         => PropertyType::class,
        'house_subtype'         => HouseSubtype::class,
        'apartment_subtype'     => ApartmentSubtype::class,
        'interior_status'       => InteriorStatus::class,
        'legal_status'          => LegalStatus::class,
        'direction'             => CompassDirection::class,
        'balcony_direction'     => CompassDirection::class,
        'usage_status'          => UsageStatus::class,
        'area'                  => 'decimal:2',
        'width'                 => 'decimal:2',
        'length'                => 'decimal:2',
        'land_area'             => 'decimal:2',
        'usable_area'           => 'decimal:2',
        'net_area'              => 'decimal:2',
        'front_road_width'      => 'decimal:2',
        'price'                 => 'decimal:0',
        'monthly_rent'          => 'decimal:0',
        'deposit'               => 'decimal:0',
        'current_rental_income' => 'decimal:0',
        'management_fee'        => 'decimal:0',
        'is_urgent'             => 'boolean',
        'is_price_negotiable'   => 'boolean',
        'is_featured'           => 'boolean',
        'sort_order'            => 'integer',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ── HasApproval contract ─────────────────────────────────────────

    /**
     * Sửa field này khi đã Approved/Published sẽ tự chuyển Pending (§2.3 spec Bán) — KHÔNG
     * gồm giá/is_featured/sort_order (trục vận hành, đổi giá không cần duyệt lại nội dung).
     */
    public function approvalWatchedAttributes(): array
    {
        return [
            'title', 'description', 'address_detail', 'property_type',
            'house_subtype', 'apartment_subtype', 'legal_status', 'direction',
            'balcony_direction', 'project_name', 'apartment_address', 'usage_status',
        ];
    }

    /**
     * HasApproval::scopePubliclyVisible() lồng whereHas('approvalSubject', ...) — đúng cho
     * entity KHÔNG tenant-scoped (PostArticle/Event, không extends TenantAwareModel), nhưng
     * RealEstateListing VÀ ApprovalSubject đều tenant-scoped (OrganizationScope) nên khách
     * vãng lai (không có TenantContext — App\Shared\Tenancy\OrganizationScope::apply() mặc
     * định WHERE 0=1 để chống rò rỉ dữ liệu) sẽ luôn nhận tập rỗng ở CẢ 2 bảng. Trang công khai
     * (/anland, /nha-dat-ban, /nha-dat-thue) gộp tin từ MỌI Organization nên phải bỏ
     * OrganizationScope ở cả 2 phía — publiclyVisible() (chỉ tin đã publish) đã là gate an toàn
     * tương đương, bypass tenant scope ở đây không phải lỗ hổng.
     *
     * Đặt tên riêng (KHÔNG ghi đè publiclyVisible()) để không âm thầm đổi hành vi scope gốc của
     * HasApproval ở những nơi khác trong module còn cần lọc theo tenant hiện tại.
     */
    public function scopePublicPortalVisible(Builder $query): Builder
    {
        return $query->withoutGlobalScope(OrganizationScope::class)
            ->whereHas('approvalSubject', function (Builder $q) {
                $q->withoutGlobalScope(OrganizationScope::class)
                    ->whereNotNull('public_snapshot')
                    ->where('status', '!=', ApprovalStatus::Archived);
            });
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isSale(): bool
    {
        return $this->listing_type === ListingType::Sale;
    }

    public function isRent(): bool
    {
        return $this->listing_type === ListingType::Rent;
    }

    /**
     * Giá hiển thị — ưu tiên `is_price_negotiable` (§0 v1.1 spec Bán) trước khi xét có số hay
     * không, KHÔNG suy diễn "thoả thuận" chỉ từ giá trị null.
     */
    public function getDisplayPriceAttribute(): string
    {
        if ($this->is_price_negotiable) {
            return 'Thoả thuận';
        }

        if ($this->isSale()) {
            return $this->price ? number_format((float) $this->price / 1_000_000_000, 1) . ' tỷ' : '—';
        }

        return $this->monthly_rent ? number_format((float) $this->monthly_rent) . ' đ/tháng' : '—';
    }

    /** spec/RealEstateForRent_Technical_Specification.md §2.2 — giá thuê + phí quản lý, chỉ có nghĩa khi isRent(). */
    public function getTotalMonthlyCostAttribute(): ?float
    {
        if (! $this->isRent() || $this->monthly_rent === null) {
            return null;
        }

        return (float) $this->monthly_rent + (float) ($this->management_fee ?? 0);
    }

    /**
     * Ảnh gallery, đã sort theo order_column (§2.4 spec Bán) — max 6, validate ở Action, không
     * phải DB constraint. Dùng `MediaUrlService` (đúng pattern `HasTenantMedia::getMediaUrl()`
     * dùng chung toàn hệ thống) — KHÔNG gọi thẳng `$media->getUrl()` của Spatie: hàm đó đòi hỏi
     * conversion phải được `registerMediaConversions()` khai báo trước trên model, trong khi
     * `MediaUrlService` tự fallback về ảnh gốc nếu conversion chưa sinh (không cần đăng ký gì).
     */
    public function galleryUrls(string $conversion = 'medium'): array
    {
        $urlService = app(\App\Services\Media\MediaUrlService::class);

        return $this->getMedia('real_estate_gallery')->map(
            fn ($media) => $urlService->url($media, $conversion)
        )->all();
    }

    // ── Scout / Meilisearch ────────────────────────────────────────────

    public function searchableAs(): string
    {
        return config('scout.prefix') . 'real_estate_listings';
    }

    /** Chỉ đẩy vào Meilisearch tin đã publiclyVisible() — SoftDeletes tự gọi unsearchable() khi bị xoá. */
    public function shouldBeSearchable(): bool
    {
        return $this->isPubliclyVisible();
    }

    public function toSearchableArray(): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'listing_type'   => $this->listing_type->value,
            'property_type'  => $this->property_type->value,
            'title'          => $this->title,
            'description'    => (string) $this->description,
            'slug'           => $this->slug,
            'province_code'  => $this->province_code,
            'ward_code'      => $this->ward_code,
            'price'          => $this->price !== null ? (float) $this->price : null,
            'monthly_rent'   => $this->monthly_rent !== null ? (float) $this->monthly_rent : null,
            'area'           => $this->area !== null ? (float) $this->area : null,
            'bedrooms'       => $this->bedrooms,
            'bathrooms'      => $this->bathrooms,
            'is_featured'    => (bool) $this->is_featured,
            'published_at'   => $this->created_at?->timestamp,
        ];
    }
}
