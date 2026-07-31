<?php

namespace Modules\RealEstate\Database\Seeders;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Modules\Approval\Actions\ApproveAction;
use Modules\Approval\Actions\PublishAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\RealEstate\Features\ListingManagement\Actions\CreateRealEstateListingAction;
use Modules\RealEstate\Features\ListingManagement\Data\RealEstateListingData;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * Demo content cho portal Anland (/anland) — tin BĐS mẫu ĐÃ publish, để trang chủ có dữ liệu
 * thật render (Anland_Technical_Specification — trang chủ portal riêng, tách biệt
 * familiesforlife). Chạy Action THẬT (create → submit → approve → publish), cùng nguyên tắc
 * Modules\Product\Database\Seeders\ProductApprovalDemoSeeder / Modules\Event\Database\Seeders\
 * EventDemoSeeder — KHÔNG insert thẳng DB.
 *
 * Tài khoản: marketing@demo.test (Organization "demo" — tạo/gửi duyệt, có real_estate.create/
 * edit qua config/permissions.php), moderator@system.local (platform_content_moderator — duyệt
 * + publish, RealEstateListingPolicy::approve()/publishApproval()).
 *
 * Idempotent — mỗi tin dùng slug cố định, bỏ qua nếu đã tồn tại.
 */
class RealEstateListingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo')->first();

        if (! $org) {
            $this->command->warn('  ⚠ Không tìm thấy Organization slug=demo — chạy OrganizationSeeder trước.');

            return;
        }

        $marketing = User::where('email', 'marketing@demo.test')->first();
        $moderator = User::withoutGlobalScopes()->where('email', 'moderator@system.local')->first();

        if (! $marketing || ! $moderator) {
            $this->command->warn('  ⚠ Thiếu tài khoản demo (marketing@demo.test / moderator@system.local) — chạy UserSeeder + ContentModeratorSeeder trước.');

            return;
        }

        TenantContext::set($org);
        $previousUser = Auth::user();

        $created = 0;
        foreach ($this->definitions() as $definition) {
            if ($this->seedListing($definition, $marketing, $moderator)) {
                $created++;
            }
        }

        TenantContext::flush();
        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info("  ✓ Anland demo listings seeded ({$created} tin mới, đã published).");
    }

    /** @return array<int, array<string, mixed>> */
    private function definitions(): array
    {
        return [
            // ── Nhà bán ──────────────────────────────────────────────────
            [
                'slug' => 'demo-anland-nha-pho-cau-giay', 'listing_type' => 'sale', 'property_type' => 'house',
                'title' => 'Nhà phố mặt tiền Cầu Giấy, 4 tầng, kinh doanh sầm uất (Demo)',
                'description' => 'Nhà xây kiên cố 4 tầng, mặt tiền rộng, khu dân cư đông đúc, tiện kinh doanh mặt bằng tầng 1.',
                'address_detail' => 'Đường Cầu Giấy', 'province_code' => '01', 'ward_code' => '00004',
                'house_subtype' => 'street', 'width' => 5, 'length' => 16, 'land_area' => 80, 'floors' => 4,
                'bedrooms' => 4, 'bathrooms' => 3, 'interior_status' => 'day_du', 'legal_status' => 'so_hong_rieng',
                'direction' => 'dong_nam', 'price' => 12_500_000_000, 'is_featured' => true,
            ],
            [
                'slug' => 'demo-anland-nha-hem-thanh-xuan', 'listing_type' => 'sale', 'property_type' => 'house',
                'title' => 'Nhà hẻm ô tô Thanh Xuân, 3 tầng mới xây (Demo)',
                'description' => 'Hẻm xe hơi thông thoáng, nhà mới xây 3 tầng, thiết kế hiện đại, an ninh khu vực tốt.',
                'address_detail' => 'Phường Thanh Xuân', 'province_code' => '01', 'ward_code' => '00004',
                'house_subtype' => 'alley', 'width' => 4.5, 'length' => 14, 'land_area' => 63, 'floors' => 3,
                'bedrooms' => 3, 'bathrooms' => 2, 'interior_status' => 'co_ban', 'legal_status' => 'so_hong_rieng',
                'direction' => 'nam', 'price' => 6_800_000_000,
            ],
            [
                'slug' => 'demo-anland-biet-thu-thu-duc', 'listing_type' => 'sale', 'property_type' => 'house',
                'title' => 'Biệt thự sân vườn Thủ Đức, view sông thoáng mát (Demo)',
                'description' => 'Biệt thự 2 mặt tiền, sân vườn rộng, hồ bơi riêng, khu compound an ninh 24/7.',
                'address_detail' => 'Khu đô thị ven sông', 'province_code' => '79', 'ward_code' => '25747',
                'house_subtype' => 'villa', 'width' => 12, 'length' => 20, 'land_area' => 240, 'floors' => 3,
                'bedrooms' => 5, 'bathrooms' => 5, 'interior_status' => 'day_du', 'legal_status' => 'so_hong_rieng',
                'direction' => 'dong', 'price' => 28_000_000_000, 'is_featured' => true,
            ],
            [
                'slug' => 'demo-anland-nha-lien-ke-binh-thanh', 'listing_type' => 'sale', 'property_type' => 'house',
                'title' => 'Nhà liền kề khu compound Bình Thạnh, sổ hồng riêng (Demo)',
                'description' => 'Nhà liền kề trong khu quy hoạch, đồng bộ hạ tầng, gần trường học và trung tâm thương mại.',
                'address_detail' => 'Khu dân cư quy hoạch', 'province_code' => '79', 'ward_code' => '25747',
                'house_subtype' => 'adjacent', 'width' => 5, 'length' => 18, 'land_area' => 90, 'floors' => 3,
                'bedrooms' => 4, 'bathrooms' => 3, 'interior_status' => 'day_du', 'legal_status' => 'so_hong_rieng',
                'direction' => 'tay_nam', 'price' => 9_200_000_000,
            ],

            // ── Căn hộ bán ───────────────────────────────────────────────
            [
                'slug' => 'demo-anland-can-ho-vinhomes-demo', 'listing_type' => 'sale', 'property_type' => 'apartment',
                'title' => 'Căn hộ 2PN dự án cao cấp view hồ, nội thất đầy đủ (Demo)',
                'description' => 'Căn góc 2 phòng ngủ, ban công view hồ trung tâm, nội thất cao cấp bàn giao đầy đủ.',
                'address_detail' => 'Tầng cao, block A', 'province_code' => '01', 'ward_code' => '00004',
                'apartment_subtype' => 'apartment', 'usable_area' => 72, 'net_area' => 68, 'bedrooms' => 2, 'bathrooms' => 2,
                'interior_status' => 'day_du', 'legal_status' => 'so_hong_rieng', 'direction' => 'dong_bac',
                'balcony_direction' => 'dong_nam', 'project_name' => 'Golden Riverside (Demo)', 'apartment_address' => 'Tòa A',
                'usage_status' => 'nha_trong', 'management_fee' => 850_000, 'price' => 3_450_000_000, 'is_featured' => true,
            ],
            [
                'slug' => 'demo-anland-officetel-quan1', 'listing_type' => 'sale', 'property_type' => 'apartment',
                'title' => 'Officetel trung tâm Quận 1, phù hợp đầu tư cho thuê (Demo)',
                'description' => 'Officetel diện tích nhỏ gọn, vị trí trung tâm, phù hợp cho thuê văn phòng/lưu trú ngắn hạn.',
                'address_detail' => 'Trung tâm quận', 'province_code' => '79', 'ward_code' => '25747',
                'apartment_subtype' => 'officetel', 'usable_area' => 35, 'net_area' => 33, 'bedrooms' => 1, 'bathrooms' => 1,
                'interior_status' => 'day_du', 'legal_status' => 'hop_dong', 'direction' => 'tay',
                'balcony_direction' => 'nam', 'project_name' => 'Landmark Office (Demo)', 'apartment_address' => 'Tòa B',
                'usage_status' => 'dang_cho_thue', 'management_fee' => 600_000, 'price' => 2_100_000_000,
            ],
            [
                'slug' => 'demo-anland-penthouse-hcm', 'listing_type' => 'sale', 'property_type' => 'apartment',
                'title' => 'Penthouse 3 tầng, sân vườn trên cao, view toàn thành phố (Demo)',
                'description' => 'Penthouse duplex 3 tầng riêng biệt, sân vườn trên cao, tầm nhìn bao trọn thành phố.',
                'address_detail' => 'Tầng cao nhất', 'province_code' => '79', 'ward_code' => '25747',
                'apartment_subtype' => 'penthouse', 'usable_area' => 220, 'net_area' => 205, 'bedrooms' => 4, 'bathrooms' => 4,
                'interior_status' => 'day_du', 'legal_status' => 'so_hong_rieng', 'direction' => 'dong_nam',
                'balcony_direction' => 'dong', 'project_name' => 'Sky Panorama (Demo)', 'apartment_address' => 'Tòa Sky',
                'usage_status' => 'nha_trong', 'management_fee' => 2_500_000, 'price' => 18_900_000_000, 'is_featured' => true,
            ],

            // ── Đất bán ──────────────────────────────────────────────────
            [
                'slug' => 'demo-anland-dat-nen-dong-anh', 'listing_type' => 'sale', 'property_type' => 'land',
                'title' => 'Đất nền thổ cư Đông Anh, mặt tiền đường lớn (Demo)',
                'description' => 'Lô đất vuông vắn, mặt tiền đường nhựa rộng, gần khu công nghiệp, tiềm năng tăng giá.',
                'address_detail' => 'Khu vực Đông Anh', 'province_code' => '01', 'ward_code' => '00004',
                'width' => 8, 'length' => 20, 'land_area' => 160, 'front_road_width' => 8, 'legal_status' => 'so_hong_rieng',
                'direction' => 'bac', 'price' => 4_800_000_000,
            ],
            [
                'slug' => 'demo-anland-dat-vuon-cu-chi', 'listing_type' => 'sale', 'property_type' => 'land',
                'title' => 'Đất vườn Củ Chi, phù hợp làm nhà vườn nghỉ dưỡng (Demo)',
                'description' => 'Đất vườn diện tích lớn, không khí trong lành, phù hợp xây nhà vườn hoặc trang trại nhỏ.',
                'address_detail' => 'Khu vực Củ Chi', 'province_code' => '79', 'ward_code' => '25747',
                'width' => 20, 'length' => 50, 'land_area' => 1000, 'front_road_width' => 6, 'legal_status' => 'so_hong_chung',
                'direction' => 'tay', 'price' => 3_200_000_000,
            ],
            [
                'slug' => 'demo-anland-dat-mat-tien-quoc-lo', 'listing_type' => 'sale', 'property_type' => 'land',
                'title' => 'Đất mặt tiền quốc lộ, kinh doanh đa ngành (Demo)',
                'description' => 'Vị trí mặt tiền quốc lộ đông xe qua lại, phù hợp kinh doanh cửa hàng, kho bãi.',
                'address_detail' => 'Mặt tiền quốc lộ', 'province_code' => '01', 'ward_code' => '00004',
                'width' => 15, 'length' => 30, 'land_area' => 450, 'front_road_width' => 15, 'legal_status' => 'so_hong_rieng',
                'direction' => 'dong', 'price' => 15_600_000_000, 'is_urgent' => true, 'urgent_days' => 14,
            ],

            // ── Nhà cho thuê ─────────────────────────────────────────────
            [
                'slug' => 'demo-anland-nha-cho-thue-cau-giay', 'listing_type' => 'rent', 'property_type' => 'house',
                'title' => 'Nhà nguyên căn cho thuê Cầu Giấy, 3 tầng đầy đủ nội thất (Demo)',
                'description' => 'Nhà nguyên căn 3 tầng, nội thất đầy đủ, phù hợp gia đình hoặc làm văn phòng nhỏ.',
                'address_detail' => 'Gần trục đường chính', 'province_code' => '01', 'ward_code' => '00004',
                'area' => 75, 'bedrooms' => 3, 'bathrooms' => 2, 'floors' => 3, 'interior_status' => 'day_du',
                'legal_status' => 'so_hong_rieng', 'direction' => 'nam', 'monthly_rent' => 18_000_000,
                'deposit' => 36_000_000, 'rental_period_months' => 12,
            ],
            [
                'slug' => 'demo-anland-nha-cho-thue-thu-duc', 'listing_type' => 'rent', 'property_type' => 'house',
                'title' => 'Nhà cấp 4 cho thuê Thủ Đức, sân rộng để xe ô tô (Demo)',
                'description' => 'Nhà cấp 4 sân rộng, an ninh khu vực tốt, tiện để xe ô tô trong nhà.',
                'address_detail' => 'Khu dân cư yên tĩnh', 'province_code' => '79', 'ward_code' => '25747',
                'area' => 60, 'bedrooms' => 2, 'bathrooms' => 1, 'floors' => 1, 'interior_status' => 'co_ban',
                'legal_status' => 'so_hong_rieng', 'direction' => 'dong', 'monthly_rent' => 7_500_000,
                'deposit' => 15_000_000, 'rental_period_months' => 6,
            ],

            // ── Căn hộ cho thuê ──────────────────────────────────────────
            [
                'slug' => 'demo-anland-can-ho-cho-thue-q7', 'listing_type' => 'rent', 'property_type' => 'apartment',
                'title' => 'Căn hộ 1PN cho thuê Quận 7, đầy đủ nội thất, vào ở ngay (Demo)',
                'description' => 'Căn hộ 1 phòng ngủ, nội thất đầy đủ, gần trường quốc tế và siêu thị.',
                'address_detail' => 'Khu Nam Sài Gòn', 'province_code' => '79', 'ward_code' => '25747',
                'usable_area' => 45, 'bedrooms' => 1, 'bathrooms' => 1, 'interior_status' => 'day_du',
                'direction' => 'tay_bac', 'balcony_direction' => 'dong_nam', 'project_name' => 'Riverpark (Demo)',
                'apartment_address' => 'Tòa C', 'usage_status' => 'nha_trong', 'management_fee' => 550_000,
                'monthly_rent' => 9_500_000, 'deposit' => 19_000_000, 'rental_period_months' => 12, 'is_featured' => true,
            ],
            [
                'slug' => 'demo-anland-can-ho-cho-thue-cau-giay', 'listing_type' => 'rent', 'property_type' => 'apartment',
                'title' => 'Căn hộ 2PN cho thuê Cầu Giấy, view đẹp, an ninh 24/7 (Demo)',
                'description' => 'Căn hộ 2 phòng ngủ thoáng mát, toà nhà có bảo vệ 24/7, gần trung tâm thương mại.',
                'address_detail' => 'Khu chung cư cao tầng', 'province_code' => '01', 'ward_code' => '00004',
                'usable_area' => 68, 'bedrooms' => 2, 'bathrooms' => 2, 'interior_status' => 'day_du',
                'direction' => 'dong', 'balcony_direction' => 'nam', 'project_name' => 'Green Tower (Demo)',
                'apartment_address' => 'Tòa D', 'usage_status' => 'nha_trong', 'management_fee' => 700_000,
                'monthly_rent' => 12_000_000, 'deposit' => 24_000_000, 'rental_period_months' => 12,
            ],

            // ── Mặt bằng cho thuê ────────────────────────────────────────
            [
                'slug' => 'demo-anland-mat-bang-kinh-doanh-q1', 'listing_type' => 'rent', 'property_type' => 'layout',
                'title' => 'Mặt bằng kinh doanh mặt tiền Quận 1, khu vực sầm uất (Demo)',
                'description' => 'Mặt bằng tầng trệt mặt tiền đường lớn, khu vực đông người qua lại, phù hợp mở cửa hàng/quán ăn.',
                'address_detail' => 'Mặt tiền đường lớn', 'province_code' => '79', 'ward_code' => '25747',
                'area' => 90, 'monthly_rent' => 45_000_000, 'deposit' => 90_000_000, 'rental_period_months' => 12,
            ],
        ];
    }

    private function seedListing(array $def, User $marketing, User $moderator): bool
    {
        if (RealEstateListing::where('slug', $def['slug'])->exists()) {
            return false;
        }

        Auth::login($marketing);

        $data = RealEstateListingData::from($def);
        $listing = app(CreateRealEstateListingAction::class)->handle($data);

        // slug do Action tự sinh từ title nếu $data->slug rỗng — ở đây luôn truyền slug cố định
        // (định danh 'slug' trong $def) nên không cần ghi đè lại như EventDemoSeeder.

        app(SubmitForApprovalAction::class)->handle($listing);

        Auth::login($moderator);
        app(ApproveAction::class)->handle($listing->fresh());
        app(PublishAction::class)->handle($listing->fresh());

        return true;
    }
}
