<?php

namespace Modules\RealEstate\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RealEstate\Enums\ListingType;
use Modules\RealEstate\Features\PublicReading\Queries\ListPublicRealEstateListingsHandler;
use Modules\RealEstate\Features\PublicReading\Queries\ListPublicRealEstateListingsQuery;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * spec/RealEstateForSale_Technical_Specification.md §7 — render công khai `/nha-dat-ban`,
 * spec/RealEstateForRent_Technical_Specification.md §4 — `/nha-dat-thue`. CÙNG Query/Handler
 * cho cả 2 loại (chỉ đổi tham số listingType) — không viết Handler riêng cho Thuê.
 */
class PublicRealEstateController extends Controller
{
    public function saleIndex(Request $request, ListPublicRealEstateListingsHandler $handler): View
    {
        return view('realestate::public.listings.index', [
            'listings'    => $handler->handle($this->buildQuery($request, ListingType::Sale)),
            'listingType' => ListingType::Sale,
        ]);
    }

    public function rentIndex(Request $request, ListPublicRealEstateListingsHandler $handler): View
    {
        return view('realestate::public.listings.index', [
            'listings'    => $handler->handle($this->buildQuery($request, ListingType::Rent)),
            'listingType' => ListingType::Rent,
        ]);
    }

    public function saleShow(string $slug, int $id): View
    {
        return $this->show($slug, $id, ListingType::Sale);
    }

    public function rentShow(string $slug, int $id): View
    {
        return $this->show($slug, $id, ListingType::Rent);
    }

    /** 404 nếu không publiclyVisible() hoặc listing_type khác trang đang xem (§7.3 spec Bán). */
    private function show(string $slug, int $id, ListingType $listingType): View
    {
        $listing = RealEstateListing::publiclyVisible()
            ->where('id', $id)
            ->where('slug', $slug)
            ->where('listing_type', $listingType)
            ->firstOrFail();

        // HasApproval::publicContent() — field "nội dung" (approvalWatchedAttributes()) lấy từ
        // bản đã duyệt/đóng băng, KHÔNG đọc thẳng $listing->title khi hiển thị công khai.
        $content = $listing->publicContent();

        return view('realestate::public.listings.show', compact('listing', 'content'));
    }

    private function buildQuery(Request $request, ListingType $listingType): ListPublicRealEstateListingsQuery
    {
        return new ListPublicRealEstateListingsQuery(
            listingType: $listingType,
            page: max(1, $request->integer('page', 1)),
            perPage: config('realestate.listings_per_page', 12),
            propertyType: $request->string('property_type')->value() ?: null,
            provinceCode: $request->string('province_code')->value() ?: null,
            priceMin: $request->filled('price_min') ? $request->float('price_min') : null,
            priceMax: $request->filled('price_max') ? $request->float('price_max') : null,
            bedrooms: $request->filled('bedrooms') ? $request->integer('bedrooms') : null,
            areaMin: $request->filled('area_min') ? $request->float('area_min') : null,
            areaMax: $request->filled('area_max') ? $request->float('area_max') : null,
        );
    }
}
