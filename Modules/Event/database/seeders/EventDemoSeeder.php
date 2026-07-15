<?php

namespace Modules\Event\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Event\Features\EventCategoryManagement\Actions\CreateEventCategoryAction;
use Modules\Event\Features\EventCategoryManagement\Data\EventCategoryData;
use Modules\Event\Features\EventModeration\Actions\ApproveEventAction;
use Modules\Event\Features\EventModeration\Actions\CreateEventAction;
use Modules\Event\Features\EventModeration\Actions\PublishEventAction;
use Modules\Event\Features\EventModeration\Data\EventData;
use Modules\Event\Models\Event;
use Modules\Event\Models\EventCategory;

/**
 * Demo content cho Event (sự kiện đã xuất bản) — chạy Action THẬT (create → approve →
 * publish) qua 2 tài khoản nền tảng platform_content_editor/platform_content_head
 * (Modules\Approval\Database\Seeders\ContentReviewHierarchySeeder) + tài khoản viết
 * platform_content_creator (PlatformContentCreatorSeeder), cùng nguyên tắc Modules\Post\
 * Database\Seeders\PostDemoSeeder — nên seeder này chạy SAU ApprovalDatabaseSeeder.
 *
 * Idempotent — mỗi sự kiện dùng slug cố định, bỏ qua nếu đã tồn tại.
 */
class EventDemoSeeder extends Seeder
{
    /** @var array<int, array{key: string, name: string, icon: string, color: string}> */
    private const CATEGORIES = [
        ['key' => 'parenting_workshop', 'name' => 'Hội thảo phụ huynh',       'icon' => 'presentation', 'color' => '#3B82F6'],
        ['key' => 'family_day',         'name' => 'Ngày hội gia đình',        'icon' => 'users',        'color' => '#F59E0B'],
        ['key' => 'kids_camp',          'name' => 'Trại hè thiếu nhi',        'icon' => 'tent',         'color' => '#10B981'],
        ['key' => 'expo',               'name' => 'Triển lãm & Hội chợ',      'icon' => 'store',        'color' => '#8B5CF6'],
        ['key' => 'skills_course',      'name' => 'Khóa học kỹ năng',         'icon' => 'book-open',    'color' => '#EC4899'],
        ['key' => 'community',          'name' => 'Sự kiện cộng đồng',        'icon' => 'globe',        'color' => '#06B6D4'],
        ['key' => 'charity',            'name' => 'Chương trình từ thiện',    'icon' => 'hand-heart',   'color' => '#EF4444'],
        ['key' => 'family_sports',      'name' => 'Thể thao gia đình',        'icon' => 'medal',        'color' => '#84CC16'],
    ];

    public function run(): void
    {
        $creator = User::withoutGlobalScopes()->where('email', 'content-creator@system.local')->first();
        $editor  = User::withoutGlobalScopes()->where('email', 'editor@system.local')->first();
        $head    = User::withoutGlobalScopes()->where('email', 'content-head@system.local')->first();

        if (! $creator || ! $editor || ! $head) {
            $this->command->warn('  ⚠ Thiếu tài khoản platform (content-creator/editor/content-head@system.local) — chạy ApprovalDatabaseSeeder trước.');

            return;
        }

        $previousUser = Auth::user();

        $categories = $this->seedCategories($creator);

        $created = 0;
        foreach ($this->events($categories) as $definition) {
            if ($this->seedEvent($definition, $creator, $editor, $head)) {
                $created++;
            }
        }

        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info("  ✓ Event demo data seeded ({$created} sự kiện mới, đã published).");
    }

    /** @return array<string, int> map category key → EventCategory id */
    private function seedCategories(User $creator): array
    {
        Auth::login($creator);

        $map = [];

        foreach (self::CATEGORIES as $sortOrder => $definition) {
            $category = EventCategory::where('slug', Str::slug($definition['name']))->first();

            if (! $category) {
                $category = app(CreateEventCategoryAction::class)->handle(EventCategoryData::from([
                    'name'       => $definition['name'],
                    'icon'       => $definition['icon'],
                    'color_hex'  => $definition['color'],
                    'sort_order' => $sortOrder,
                ]));
            }

            $map[$definition['key']] = $category->id;
        }

        return $map;
    }

    /** @return array<int, array<string, mixed>> */
    private function events(array $categories): array
    {
        return [
            [
                'category_id'   => $categories['parenting_workshop'],
                'title'         => 'Hội thảo "Nuôi dạy con không nước mắt" (Demo)',
                'short_title'   => 'Nuôi dạy con không nước mắt',
                'slug'          => 'demo-hoi-thao-nuoi-day-con-khong-nuoc-mat',
                'description'   => 'Hội thảo chia sẻ phương pháp kỷ luật tích cực cùng chuyên gia tâm lý gia đình, dành cho cha mẹ có con từ 3-12 tuổi.',
                'start_offset'  => 10,
                'duration'      => 0,
                'start_time'    => '08:30',
                'end_time'      => '11:30',
                'location_type' => 'physical',
                'venue_name'    => 'Trung tâm Hội nghị Quốc gia',
                'venue_address' => '57 Phạm Hùng',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/hoi-thao-nuoi-day-con',
                'price_type'    => 'free',
                'poster_path'   => 'events/posters/demo-hoi-thao-nuoi-day-con.jpg',
                'is_featured'   => true,
            ],
            [
                'category_id'   => $categories['parenting_workshop'],
                'title'         => 'Tọa đàm trực tuyến "Đồng hành cùng con tuổi dậy thì" (Demo)',
                'short_title'   => 'Đồng hành cùng con tuổi dậy thì',
                'slug'          => 'demo-toa-dam-dong-hanh-cung-con-tuoi-day-thi',
                'description'   => 'Tọa đàm trực tuyến cùng chuyên gia tâm lý, giải đáp thắc mắc của phụ huynh về giai đoạn dậy thì của con.',
                'start_offset'  => 15,
                'duration'      => 0,
                'start_time'    => '19:30',
                'end_time'      => '21:00',
                'location_type' => 'online',
                'online_url'    => 'https://meet.familiesforlife.test/toa-dam-tuoi-day-thi',
                'website_url'   => 'https://familiesforlife.test/su-kien/toa-dam-tuoi-day-thi',
                'price_type'    => 'free',
                'poster_path'   => 'events/posters/demo-toa-dam-tuoi-day-thi.jpg',
            ],
            [
                'category_id'   => $categories['family_day'],
                'title'         => 'Ngày hội gia đình hạnh phúc 2026 (Demo)',
                'short_title'   => 'Ngày hội gia đình hạnh phúc 2026',
                'slug'          => 'demo-ngay-hoi-gia-dinh-hanh-phuc-2026',
                'description'   => 'Chuỗi hoạt động vui chơi, trò chơi vận động và ẩm thực dành cho các gia đình, tổ chức thường niên nhân Ngày Gia đình Việt Nam.',
                'start_offset'  => 20,
                'duration'      => 1,
                'start_time'    => '08:00',
                'end_time'      => '17:00',
                'location_type' => 'physical',
                'venue_name'    => 'Công viên Thống Nhất',
                'venue_address' => 'Số 1 Trần Nhân Tông',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/ngay-hoi-gia-dinh-2026',
                'price_type'    => 'single',
                'price_amount'  => 50000,
                'poster_path'   => 'events/posters/demo-ngay-hoi-gia-dinh.jpg',
                'is_featured'   => true,
            ],
            [
                'category_id'   => $categories['family_day'],
                'title'         => 'Ngày hội "Cả nhà cùng vào bếp" (Demo)',
                'short_title'   => 'Cả nhà cùng vào bếp',
                'slug'          => 'demo-ngay-hoi-ca-nha-cung-vao-bep',
                'description'   => 'Hoạt động nấu ăn cùng nhau giữa cha mẹ và con cái, hướng dẫn bởi đầu bếp chuyên nghiệp, giúp gắn kết yêu thương trong gia đình.',
                'start_offset'  => 25,
                'duration'      => 0,
                'start_time'    => '09:00',
                'end_time'      => '12:00',
                'location_type' => 'physical',
                'venue_name'    => 'Nhà Văn hóa Thanh niên',
                'venue_address' => '4 Phạm Ngọc Thạch',
                'province_code' => '79',
                'ward_code'     => '25747',
                'website_url'   => 'https://familiesforlife.test/su-kien/ca-nha-cung-vao-bep',
                'price_type'    => 'single',
                'price_amount'  => 150000,
                'poster_path'   => 'events/posters/demo-ca-nha-cung-vao-bep.jpg',
            ],
            [
                'category_id'   => $categories['kids_camp'],
                'title'         => 'Trại hè "Kỹ năng sinh tồn nhí" 2026 (Demo)',
                'short_title'   => 'Kỹ năng sinh tồn nhí 2026',
                'slug'          => 'demo-trai-he-ky-nang-sinh-ton-nhi-2026',
                'description'   => 'Trại hè 3 ngày 2 đêm giúp trẻ 8-14 tuổi rèn luyện kỹ năng sinh tồn, làm việc nhóm và tính tự lập trong môi trường thiên nhiên.',
                'start_offset'  => 40,
                'duration'      => 2,
                'start_time'    => '07:00',
                'end_time'      => null,
                'location_type' => 'physical',
                'venue_name'    => 'Khu du lịch sinh thái Ba Vì',
                'venue_address' => 'Xã Vân Hòa',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/trai-he-sinh-ton-nhi',
                'price_type'    => 'single',
                'price_amount'  => 2500000,
                'poster_path'   => 'events/posters/demo-trai-he-sinh-ton-nhi.jpg',
                'is_featured'   => true,
            ],
            [
                'category_id'   => $categories['kids_camp'],
                'title'         => 'Trại hè tiếng Anh "Little Explorers" (Demo)',
                'short_title'   => 'Trại hè tiếng Anh Little Explorers',
                'slug'          => 'demo-trai-he-tieng-anh-little-explorers',
                'description'   => 'Chương trình trại hè kết hợp học tiếng Anh qua trải nghiệm thực tế dành cho trẻ 6-11 tuổi, giáo viên bản ngữ đồng hành xuyên suốt.',
                'start_offset'  => 45,
                'duration'      => 4,
                'start_time'    => '08:00',
                'end_time'      => '16:30',
                'location_type' => 'physical',
                'venue_name'    => 'Trường Liên cấp Quốc tế Gateway',
                'venue_address' => '628 Đường Đại Từ',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/little-explorers',
                'price_type'    => 'range',
                'price_min'     => 3500000,
                'price_max'     => 5000000,
                'poster_path'   => 'events/posters/demo-little-explorers.jpg',
            ],
            [
                'category_id'   => $categories['expo'],
                'title'         => 'Hội chợ đồ dùng mẹ và bé Xuân 2026 (Demo)',
                'short_title'   => 'Hội chợ đồ dùng mẹ và bé Xuân 2026',
                'slug'          => 'demo-hoi-cho-do-dung-me-va-be-xuan-2026',
                'description'   => 'Hội chợ quy tụ hơn 100 gian hàng sản phẩm mẹ và bé chính hãng, nhiều ưu đãi hấp dẫn dịp đầu năm.',
                'start_offset'  => 30,
                'duration'      => 2,
                'start_time'    => '09:00',
                'end_time'      => '21:00',
                'location_type' => 'physical',
                'venue_name'    => 'SECC Sự kiện & Triển lãm Sài Gòn',
                'venue_address' => '799 Nguyễn Văn Linh',
                'province_code' => '79',
                'ward_code'     => '25747',
                'website_url'   => 'https://familiesforlife.test/su-kien/hoi-cho-me-va-be-xuan-2026',
                'price_type'    => 'free',
                'poster_path'   => 'events/posters/demo-hoi-cho-me-va-be.jpg',
            ],
            [
                'category_id'   => $categories['expo'],
                'title'         => 'Triển lãm đồ chơi giáo dục STEM (Demo)',
                'short_title'   => 'Triển lãm đồ chơi giáo dục STEM',
                'slug'          => 'demo-trien-lam-do-choi-giao-duc-stem',
                'description'   => 'Không gian trải nghiệm đồ chơi giáo dục STEM giúp trẻ vừa học vừa chơi, dành cho phụ huynh có con từ 4-12 tuổi.',
                'start_offset'  => 35,
                'duration'      => 1,
                'start_time'    => '09:00',
                'end_time'      => '18:00',
                'location_type' => 'physical',
                'venue_name'    => 'Cung Văn hóa Hữu nghị Việt Xô',
                'venue_address' => '91 Trần Hưng Đạo',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/trien-lam-do-choi-stem',
                'price_type'    => 'single',
                'price_amount'  => 30000,
                'poster_path'   => 'events/posters/demo-trien-lam-stem.jpg',
            ],
            [
                'category_id'   => $categories['skills_course'],
                'title'         => 'Khóa học sơ cấp cứu cơ bản cho cha mẹ (Demo)',
                'short_title'   => 'Sơ cấp cứu cơ bản cho cha mẹ',
                'slug'          => 'demo-khoa-hoc-so-cap-cuu-co-ban-cho-cha-me',
                'description'   => 'Khóa học 1 ngày trang bị kỹ năng sơ cấp cứu cơ bản cho cha mẹ, xử lý các tình huống khẩn cấp thường gặp ở trẻ nhỏ.',
                'start_offset'  => 12,
                'duration'      => 0,
                'start_time'    => '08:00',
                'end_time'      => '16:00',
                'location_type' => 'physical',
                'venue_name'    => 'Bệnh viện Nhi Trung ương',
                'venue_address' => '18/879 La Thành',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/so-cap-cuu-co-ban',
                'price_type'    => 'single',
                'price_amount'  => 300000,
                'poster_path'   => 'events/posters/demo-so-cap-cuu.jpg',
                'is_featured'   => true,
            ],
            [
                'category_id'   => $categories['skills_course'],
                'title'         => 'Khóa học online "Kỷ luật tích cực trong 30 ngày" (Demo)',
                'short_title'   => 'Kỷ luật tích cực trong 30 ngày',
                'slug'          => 'demo-khoa-hoc-ky-luat-tich-cuc-30-ngay',
                'description'   => 'Khóa học online 30 ngày với video bài giảng và bài tập thực hành hằng ngày, giúp cha mẹ áp dụng kỷ luật tích cực một cách bài bản.',
                'start_offset'  => 5,
                'duration'      => 29,
                'start_time'    => null,
                'end_time'      => null,
                'location_type' => 'online',
                'online_url'    => 'https://hoctap.familiesforlife.test/ky-luat-tich-cuc',
                'website_url'   => 'https://familiesforlife.test/su-kien/ky-luat-tich-cuc-30-ngay',
                'price_type'    => 'single',
                'price_amount'  => 890000,
                'poster_path'   => 'events/posters/demo-ky-luat-tich-cuc-30-ngay.jpg',
            ],
            [
                'category_id'   => $categories['community'],
                'title'         => 'Buổi giao lưu cộng đồng cha mẹ đơn thân (Demo)',
                'short_title'   => 'Giao lưu cộng đồng cha mẹ đơn thân',
                'slug'          => 'demo-giao-luu-cong-dong-cha-me-don-than',
                'description'   => 'Không gian chia sẻ, kết nối và hỗ trợ lẫn nhau dành cho cộng đồng cha mẹ đơn thân, cùng chuyên gia tư vấn tâm lý.',
                'start_offset'  => 18,
                'duration'      => 0,
                'start_time'    => '14:00',
                'end_time'      => '17:00',
                'location_type' => 'physical',
                'venue_name'    => 'Trung tâm Cộng đồng Q. Cầu Giấy',
                'venue_address' => '35 Trần Quốc Hoàn',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/cha-me-don-than',
                'price_type'    => 'free',
                'poster_path'   => 'events/posters/demo-cha-me-don-than.jpg',
            ],
            [
                'category_id'   => $categories['community'],
                'title'         => 'Ngày hội đổi đồ cũ cho bé "Second Life" (Demo)',
                'short_title'   => 'Ngày hội đổi đồ cũ cho bé',
                'slug'          => 'demo-ngay-hoi-doi-do-cu-cho-be-second-life',
                'description'   => 'Sự kiện cộng đồng khuyến khích trao đổi quần áo, đồ chơi cũ còn tốt giữa các gia đình, góp phần bảo vệ môi trường.',
                'start_offset'  => 22,
                'duration'      => 0,
                'start_time'    => '08:00',
                'end_time'      => '12:00',
                'location_type' => 'physical',
                'venue_name'    => 'Sân chơi cộng đồng Ecopark',
                'venue_address' => 'Khu đô thị Ecopark',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/second-life',
                'price_type'    => 'free',
                'poster_path'   => 'events/posters/demo-second-life.jpg',
            ],
            [
                'category_id'   => $categories['charity'],
                'title'         => 'Chương trình "Áo ấm cho em" vùng cao (Demo)',
                'short_title'   => 'Áo ấm cho em vùng cao',
                'slug'          => 'demo-chuong-trinh-ao-am-cho-em-vung-cao',
                'description'   => 'Chương trình quyên góp áo ấm, sách vở cho trẻ em vùng cao trước mùa đông, kêu gọi sự chung tay của các gia đình.',
                'start_offset'  => 8,
                'duration'      => 6,
                'start_time'    => null,
                'end_time'      => null,
                'location_type' => 'online',
                'online_url'    => 'https://tuthien.familiesforlife.test/ao-am-cho-em',
                'website_url'   => 'https://familiesforlife.test/su-kien/ao-am-cho-em',
                'price_type'    => 'free',
                'poster_path'   => 'events/posters/demo-ao-am-cho-em.jpg',
                'is_featured'   => true,
            ],
            [
                'category_id'   => $categories['charity'],
                'title'         => 'Ngày hội hiến máu "Giọt hồng gia đình" (Demo)',
                'short_title'   => 'Hiến máu Giọt hồng gia đình',
                'slug'          => 'demo-ngay-hoi-hien-mau-giot-hong-gia-dinh',
                'description'   => 'Chương trình hiến máu nhân đạo dành cho các bậc phụ huynh, phối hợp cùng Viện Huyết học – Truyền máu Trung ương.',
                'start_offset'  => 28,
                'duration'      => 0,
                'start_time'    => '07:30',
                'end_time'      => '11:30',
                'location_type' => 'physical',
                'venue_name'    => 'Viện Huyết học – Truyền máu Trung ương',
                'venue_address' => '14 Trần Thái Tông',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/giot-hong-gia-dinh',
                'price_type'    => 'free',
                'poster_path'   => 'events/posters/demo-giot-hong-gia-dinh.jpg',
            ],
            [
                'category_id'   => $categories['family_sports'],
                'title'         => 'Giải chạy gia đình "Bước chân yêu thương" 2026 (Demo)',
                'short_title'   => 'Giải chạy Bước chân yêu thương 2026',
                'slug'          => 'demo-giai-chay-buoc-chan-yeu-thuong-2026',
                'description'   => 'Giải chạy phong trào dành cho cả gia đình với các cự ly 1km, 3km và 5km, lan tỏa tinh thần vận động cùng nhau.',
                'start_offset'  => 33,
                'duration'      => 0,
                'start_time'    => '05:30',
                'end_time'      => '09:00',
                'location_type' => 'physical',
                'venue_name'    => 'Công viên Yên Sở',
                'venue_address' => 'Đường Trần Đăng Ninh',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/buoc-chan-yeu-thuong-2026',
                'price_type'    => 'single',
                'price_amount'  => 250000,
                'poster_path'   => 'events/posters/demo-buoc-chan-yeu-thuong.jpg',
            ],
            [
                'category_id'   => $categories['family_sports'],
                'title'         => 'Giải bơi thiếu nhi "Kình ngư nhí" (Demo)',
                'short_title'   => 'Giải bơi thiếu nhi Kình ngư nhí',
                'slug'          => 'demo-giai-boi-thieu-nhi-kinh-ngu-nhi',
                'description'   => 'Giải bơi phong trào dành cho thiếu nhi 6-14 tuổi, khuyến khích rèn luyện thể chất và kỹ năng phòng chống đuối nước.',
                'start_offset'  => 38,
                'duration'      => 0,
                'start_time'    => '08:00',
                'end_time'      => '11:00',
                'location_type' => 'physical',
                'venue_name'    => 'Cung Thể thao dưới nước Mỹ Đình',
                'venue_address' => 'Đường Lê Đức Thọ',
                'province_code' => '01',
                'ward_code'     => '00004',
                'website_url'   => 'https://familiesforlife.test/su-kien/kinh-ngu-nhi',
                'price_type'    => 'single',
                'price_amount'  => 200000,
                'poster_path'   => 'events/posters/demo-kinh-ngu-nhi.jpg',
            ],
        ];
    }

    private function seedEvent(array $def, User $creator, User $editor, User $head): bool
    {
        if (Event::where('slug', $def['slug'])->exists()) {
            return false;
        }

        Auth::login($creator);

        $event = app(CreateEventAction::class)->handle(EventData::from([
            'category_id'       => $def['category_id'],
            'title'             => $def['title'],
            'short_title'       => $def['short_title'],
            'description'       => $def['description'],
            'start_date'        => now()->addDays($def['start_offset'])->toDateString(),
            'end_date'          => now()->addDays($def['start_offset'] + $def['duration'])->toDateString(),
            'start_time'        => $def['start_time'] ?? null,
            'end_time'          => $def['end_time'] ?? null,
            'location_type'     => $def['location_type'],
            'venue_name'        => $def['venue_name'] ?? null,
            'venue_address'     => $def['venue_address'] ?? null,
            'province_code'     => $def['province_code'] ?? null,
            'ward_code'         => $def['ward_code'] ?? null,
            'online_url'        => $def['online_url'] ?? null,
            'website_url'       => $def['website_url'],
            'price_type'        => $def['price_type'],
            'price_amount'      => $def['price_amount'] ?? null,
            'price_min'         => $def['price_min'] ?? null,
            'price_max'         => $def['price_max'] ?? null,
            'poster_path'       => $def['poster_path'],
            'poster_alt'        => $def['short_title'],
            'poster_width'      => null,
            'poster_height'     => null,
            'poster_size_bytes' => null,
            'is_featured'       => $def['is_featured'] ?? false,
        ]));

        // slug do CreateEventAction tự sinh từ title — ghi đè lại slug cố định để idempotent
        // check ở lần chạy sau khớp đúng self::events() (title demo dài, slug tự sinh có thể
        // khác định danh mong muốn).
        $event->update(['slug' => $def['slug']]);

        Auth::login($editor);
        app(ApproveEventAction::class)->handle($event->fresh());

        Auth::login($head);
        app(PublishEventAction::class)->handle($event->fresh());

        return true;
    }
}
