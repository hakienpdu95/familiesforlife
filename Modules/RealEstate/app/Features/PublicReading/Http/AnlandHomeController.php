<?php

namespace Modules\RealEstate\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\View\View;
use Modules\RealEstate\Enums\ListingType;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * Trang chủ portal Anland (/anland) — giao diện rao vặt BĐS riêng, tách biệt hoàn toàn trang
 * chủ familiesforlife (Modules\Post). CSS/JS/layout/header/footer đều là bộ riêng
 * (resources/views/public/anland/*), KHÔNG kế thừa layouts.frontend.
 */
class AnlandHomeController extends Controller
{
    public function index(): View
    {
        // scopePublicPortalVisible() — xem docblock cùng tên trên RealEstateListing (gộp tin từ
        // mọi Organization, khách vãng lai không có TenantContext). with('approvalSubject') —
        // listing-card.blade.php gọi publicContent() cho từng tin trong vòng lặp, model đọc
        // preventLazyLoading() nên PHẢI eager-load quan hệ này trước, không thể lazy load.
        $featured = RealEstateListing::publicPortalVisible()
            ->with(['approvalSubject', 'province'])
            ->where('is_featured', true)
            ->latest()
            ->limit(6)
            ->get();

        $latestSale = RealEstateListing::publicPortalVisible()
            ->with(['approvalSubject', 'province'])
            ->where('listing_type', ListingType::Sale)
            ->latest()
            ->limit(8)
            ->get();

        $latestRent = RealEstateListing::publicPortalVisible()
            ->with(['approvalSubject', 'province'])
            ->where('listing_type', ListingType::Rent)
            ->latest()
            ->limit(4)
            ->get();

        $categoryCounts = RealEstateListing::publicPortalVisible()
            ->selectRaw('property_type, listing_type, count(*) as aggregate')
            ->groupBy('property_type', 'listing_type')
            ->get()
            ->groupBy('property_type')
            ->map(fn ($rows) => $rows->sum('aggregate'));

        $provinces = Province::where('is_active', true)->orderBy('name')->get(['province_code', 'name']);

        // Badge tin cậy trên hero — tổng tin đã publish, cùng nguồn dữ liệu với categoryCounts.
        $totalListings = $categoryCounts->sum();

        return view('realestate::public.anland.home', [
            'featured'        => $featured,
            'latestSale'      => $latestSale,
            'latestRent'      => $latestRent,
            'categoryCounts'  => $categoryCounts,
            'totalListings'   => $totalListings,
            'propertyTypes'   => PropertyType::cases(),
            'provinces'       => $provinces,
        ]);
    }
}
