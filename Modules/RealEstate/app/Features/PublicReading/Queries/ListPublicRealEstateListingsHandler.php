<?php

namespace Modules\RealEstate\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * spec/RealEstateForSale_Technical_Specification.md §7.2 — danh sách công khai `/nha-dat-ban`
 * (và `/nha-dat-thue`, spec Thuê §4.1) — filter theo query param, luôn `publiclyVisible()`
 * (HasApproval::scopePubliclyVisible(), §2.3) + `listing_type` cố định theo trang. Query trực
 * tiếp Eloquent (không qua Meilisearch) — filter ở đây là exact/range trên cột thật, không
 * phải tìm kiếm full-text mờ (giá trị Meilisearch mang lại), giữ đơn giản/đáng tin cậy (§0).
 */
class ListPublicRealEstateListingsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublicRealEstateListingsQuery $query */
        // scopePublicPortalVisible() — xem docblock cùng tên trên RealEstateListing (gộp tin
        // từ mọi Organization, publiclyVisible() nhưng bỏ OrganizationScope cả 2 phía).
        // with('approvalSubject') — view gọi publicContent() cho từng tin trong vòng lặp, model
        // đọc preventLazyLoading() nên phải eager-load quan hệ này trước.
        return RealEstateListing::publicPortalVisible()
            ->with('approvalSubject')
            ->where('listing_type', $query->listingType)
            ->when($query->propertyType, fn ($q, $v) => $q->where('property_type', $v))
            ->when($query->provinceCode, fn ($q, $v) => $q->where('province_code', $v))
            ->when($query->priceMin !== null, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('price', '>=', $query->priceMin)
                ->orWhere('monthly_rent', '>=', $query->priceMin)))
            ->when($query->priceMax !== null, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('price', '<=', $query->priceMax)
                ->orWhere('monthly_rent', '<=', $query->priceMax)))
            ->when($query->bedrooms, fn ($q, $v) => $q->where('bedrooms', '>=', $v))
            ->when($query->areaMin !== null, fn ($q) => $q->where('area', '>=', $query->areaMin))
            ->when($query->areaMax !== null, fn ($q) => $q->where('area', '<=', $query->areaMax))
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
