<?php

namespace Modules\ProvinceShowcase\Database\Seeders;

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
use Modules\Ocop\Features\OcopCategoryManagement\Actions\CreateOcopCategoryAction;
use Modules\Ocop\Features\OcopCategoryManagement\Data\OcopCategoryData;
use Modules\Ocop\Features\OcopProductManagement\Actions\CreateOcopProductAction;
use Modules\Ocop\Features\OcopProductManagement\Data\OcopProductData;
use Modules\Ocop\Models\OcopCategory;
use Modules\Ocop\Models\OcopProduct;
use Modules\Post\Features\ArticleAuthoring\Actions\ApproveArticleTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\SubmitArticleForReviewAction;
use Modules\Post\Features\ArticleAuthoring\Actions\SyncContentBlocksAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;

/**
 * spec/Province_Showcase_Technical_Specification.md §8 — Phase 5: nội dung demo cụ thể cho Huế
 * (province_code=46, place_type=thanh-pho) và Cà Mau (province_code=96, place_type=tinh) — 6 bài
 * Di sản + 6 bài Ẩm thực + 10 sản phẩm OCOP + 4 sự kiện. Chạy Action THẬT (create → submit/
 * approve → publish) cùng nguyên tắc PostDemoSeeder/EventDemoSeeder — nên seeder này chạy SAU
 * ApprovalDatabaseSeeder + ProvinceShowcaseCategorySeeder + OcopDatabaseSeeder.
 *
 * KHÔNG seed banner province_top demo — banner ảnh cần file thật đi kèm (StoreBannerImageAction
 * sinh tên UUID khi upload thật); trước đây có seedBanners() hardcode image_path giả
 * ("banners/demo-province-{slug}.jpg") không có file thật phía sau → ảnh vỡ 404 trên trang công
 * khai. Tỉnh nào cần banner demo, admin tự upload qua dashboard/banners.
 *
 * Idempotent — mỗi bản ghi dùng slug/tên cố định, bỏ qua nếu đã tồn tại.
 */
class ProvinceShowcaseDemoSeeder extends Seeder
{
    private const HUE_CODE    = '46';
    private const CA_MAU_CODE = '96';

    /** Poster thật đã có sẵn trên disk (storage/app/public/events/posters/) — tái dùng cho sự
     *  kiện demo thay vì bịa đường dẫn không có file thật đứng sau (events.poster_path NOT NULL). */
    private const DEMO_POSTERS = [
        'events/posters/0f10e0d2-3562-46f9-83dc-d15369d6327e.png',
        'events/posters/6f2677c7-7184-4c23-a3c5-b787d8603bc0.png',
        'events/posters/a694e228-e897-43fb-8b53-426b26acfacb.png',
        'events/posters/cff80c84-23be-46b7-93a1-92497dd6a34b.png',
    ];

    /** @var array<int, array{province: string, category: string, title: string, slug: string, excerpt: string, body: string}> */
    private const ARTICLES = [
        // ── Huế — Di sản văn hóa ──────────────────────────────────────
        [
            'province' => 'hue', 'category' => 'di-san-van-hoa',
            'title'    => 'Đại Nội Huế — dấu ấn triều Nguyễn',
            'slug'     => 'hue-dai-noi-hue',
            'excerpt'  => 'Quần thể Đại Nội là trung tâm chính trị của triều Nguyễn suốt hơn 140 năm, nay là di sản văn hóa thế giới giữa lòng Cố đô.',
            'body'     => '<p>Đại Nội Huế nằm bên bờ sông Hương, là quần thể kiến trúc cung đình quy mô nhất còn lại của Việt Nam, gồm Hoàng thành và Tử Cấm thành với hàng trăm công trình lớn nhỏ.</p><p>Ngọ Môn, điện Thái Hòa hay Thế Miếu vẫn giữ được nét uy nghi cổ kính, thu hút du khách tìm hiểu về đời sống cung đình triều Nguyễn xưa.</p>',
        ],
        [
            'province' => 'hue', 'category' => 'di-san-van-hoa',
            'title'    => 'Chùa Thiên Mụ bên dòng sông Hương',
            'slug'     => 'hue-chua-thien-mu',
            'excerpt'  => 'Ngôi chùa cổ nhất Huế, biểu tượng gắn liền với dòng sông Hương thơ mộng và đời sống tâm linh của người Cố đô.',
            'body'     => '<p>Chùa Thiên Mụ được xây dựng từ năm 1601, nổi bật với tháp Phước Duyên bảy tầng soi bóng xuống sông Hương — hình ảnh gắn liền với Huế trong tâm thức nhiều thế hệ.</p><p>Không gian chùa yên tĩnh, nằm trên đồi Hà Khê, là điểm đến quen thuộc của du khách muốn tìm hiểu Phật giáo xứ Huế.</p>',
        ],
        [
            'province' => 'hue', 'category' => 'di-san-van-hoa',
            'title'    => 'Lăng Tự Đức — chốn thơ mộng giữa lòng Huế',
            'slug'     => 'hue-lang-tu-duc',
            'excerpt'  => 'Lăng tẩm mang đậm dấu ấn thi ca của vị vua yêu văn chương, được xem là một trong những công trình đẹp nhất triều Nguyễn.',
            'body'     => '<p>Khác với vẻ uy nghiêm thường thấy ở lăng tẩm, Lăng Tự Đức được thiết kế như một khu vườn thơ mộng với hồ nước, đình tạ và cây xanh, phản ánh tâm hồn lãng mạn của vua Tự Đức.</p><p>Đây là nơi vua thường lui tới làm thơ, ngâm vịnh lúc sinh thời, trước khi trở thành nơi an nghỉ của ông.</p>',
        ],

        // ── Huế — Ẩm thực vùng miền ───────────────────────────────────
        [
            'province' => 'hue', 'category' => 'am-thuc-vung-mien',
            'title'    => 'Bún bò Huế — hồn cốt ẩm thực Cố đô',
            'slug'     => 'hue-bun-bo-hue',
            'excerpt'  => 'Tô bún cay nồng, đậm đà hương sả và mắm ruốc — món ăn đại diện cho tinh thần ẩm thực xứ Huế.',
            'body'     => '<p>Bún bò Huế nổi bật với nước dùng ninh từ xương bò, nêm mắm ruốc và sả đặc trưng, tạo nên vị cay nồng khó lẫn với bất kỳ món bún nào khác.</p><p>Mỗi gia đình, mỗi quán ở Huế lại có một bí quyết nêm nếm riêng, nhưng đều giữ trọn tinh thần đậm đà, cay nồng vốn có của món ăn.</p>',
        ],
        [
            'province' => 'hue', 'category' => 'am-thuc-vung-mien',
            'title'    => 'Cơm hến Vỹ Dạ, món ăn dân dã trứ danh',
            'slug'     => 'hue-com-hen-vy-da',
            'excerpt'  => 'Từ nguyên liệu bình dị là con hến sông Hương, người Huế đã tạo nên một món ăn dân dã nhưng đầy tinh tế.',
            'body'     => '<p>Cơm hến kết hợp cơm nguội, hến xào, rau sống, tóp mỡ và nước hến nóng, tạo nên hương vị vừa thanh vừa đậm đà rất riêng của vùng Vỹ Dạ.</p><p>Đây là món ăn gắn liền với đời sống bình dân của người Huế, thường được bán ở các gánh hàng rong ven sông Hương.</p>',
        ],
        [
            'province' => 'hue', 'category' => 'am-thuc-vung-mien',
            'title'    => 'Bánh khoái Huế chấm nước lèo gan',
            'slug'     => 'hue-banh-khoai',
            'excerpt'  => 'Chiếc bánh giòn rụm ăn kèm nước chấm gan đặc trưng — một trong những món ăn vặt được yêu thích nhất xứ Huế.',
            'body'     => '<p>Bánh khoái được đổ trên khuôn nhỏ, nhân tôm thịt giá đỗ, chiên giòn vàng ươm, khác biệt với bánh xèo Nam Bộ ở kích thước và cách thưởng thức.</p><p>Điểm đặc trưng làm nên tên tuổi bánh khoái Huế chính là chén nước chấm gan heo pha đậu phộng béo ngậy, không thể thiếu khi ăn kèm.</p>',
        ],

        // ── Cà Mau — Di sản văn hóa ────────────────────────────────────
        [
            'province' => 'ca-mau', 'category' => 'di-san-van-hoa',
            'title'    => 'Mũi Cà Mau — điểm cực Nam Tổ quốc',
            'slug'     => 'ca-mau-mui-ca-mau',
            'excerpt'  => 'Cột mốc tọa độ quốc gia GPS 0001 đánh dấu điểm cực Nam thiêng liêng của đất nước Việt Nam.',
            'body'     => '<p>Mũi Cà Mau là điểm địa đầu cực Nam Tổ quốc, nơi du khách có thể chiêm ngưỡng cảnh mặt trời mọc ở biển Đông và lặn ở biển Tây chỉ trong cùng một ngày.</p><p>Cột mốc tọa độ GPS 0001 tại đây trở thành điểm check-in không thể bỏ lỡ của bất kỳ ai khi đặt chân đến vùng đất này.</p>',
        ],
        [
            'province' => 'ca-mau', 'category' => 'di-san-van-hoa',
            'title'    => 'Vườn quốc gia Mũi Cà Mau và rừng ngập mặn',
            'slug'     => 'ca-mau-vuon-quoc-gia',
            'excerpt'  => 'Hệ sinh thái rừng ngập mặn rộng lớn, nơi cư trú của nhiều loài động thực vật đặc trưng vùng đồng bằng sông Cửu Long.',
            'body'     => '<p>Vườn quốc gia Mũi Cà Mau là một trong những khu rừng ngập mặn lớn nhất Việt Nam, đóng vai trò quan trọng trong việc chắn sóng, chống sạt lở và điều hòa sinh thái vùng ven biển.</p><p>Du khách có thể trải nghiệm xuyên rừng đước bằng vỏ lãi, tìm hiểu hệ sinh thái ngập mặn độc đáo của miền Tây sông nước.</p>',
        ],
        [
            'province' => 'ca-mau', 'category' => 'di-san-van-hoa',
            'title'    => 'Khu Ramsar Mũi Cà Mau — lá phổi xanh miền Tây',
            'slug'     => 'ca-mau-ramsar',
            'excerpt'  => 'Vùng đất ngập nước được công nhận là khu Ramsar thứ 2088 của thế giới, mang giá trị sinh thái quốc tế.',
            'body'     => '<p>Khu Ramsar Mũi Cà Mau được UNESCO công nhận nhờ giá trị đa dạng sinh học và vai trò quan trọng trong hệ sinh thái đất ngập nước toàn cầu.</p><p>Đây cũng là nơi lưu giữ nhiều loài chim di cư quý hiếm, góp phần định vị Cà Mau trên bản đồ du lịch sinh thái quốc tế.</p>',
        ],

        // ── Cà Mau — Ẩm thực vùng miền ─────────────────────────────────
        [
            'province' => 'ca-mau', 'category' => 'am-thuc-vung-mien',
            'title'    => 'Lẩu mắm Cà Mau đậm vị miền sông nước',
            'slug'     => 'ca-mau-lau-mam',
            'excerpt'  => 'Nồi lẩu đậm đà hương mắm cá đồng, quy tụ đủ loại tôm cá rau vườn — tinh hoa ẩm thực miền sông nước.',
            'body'     => '<p>Lẩu mắm Cà Mau nấu từ mắm cá sặc hoặc cá linh, kết hợp cùng tôm, cá, thịt và hàng chục loại rau đồng, tạo nên hương vị đậm đà đặc trưng Nam Bộ.</p><p>Món ăn thường xuất hiện trong các bữa tiệc gia đình, thể hiện sự sung túc và hiếu khách của người dân vùng đất Mũi.</p>',
        ],
        [
            'province' => 'ca-mau', 'category' => 'am-thuc-vung-mien',
            'title'    => 'Ba khía Rạch Gốc trứ danh',
            'slug'     => 'ca-mau-ba-khia-rach-goc',
            'excerpt'  => 'Đặc sản muối chua nổi tiếng của vùng Rạch Gốc, món quà quê không thể thiếu khi ghé thăm Cà Mau.',
            'body'     => '<p>Ba khía Rạch Gốc được muối theo bí quyết gia truyền, giữ được vị mặn mòi đặc trưng của vùng nước lợ ven rừng đước.</p><p>Món ăn thường được trộn cùng tỏi ớt chanh đường, ăn kèm cơm trắng hoặc cuốn bánh tráng, trở thành đặc sản trứ danh của Cà Mau.</p>',
        ],
        [
            'province' => 'ca-mau', 'category' => 'am-thuc-vung-mien',
            'title'    => 'Cá lóc nướng trui kiểu Nam Bộ',
            'slug'     => 'ca-mau-ca-loc-nuong-trui',
            'excerpt'  => 'Món ăn dân dã gắn liền với đời sống miền sông nước, nướng nguyên con trên rơm giữ trọn vị ngọt tự nhiên.',
            'body'     => '<p>Cá lóc nướng trui là món ăn đặc trưng của người dân Nam Bộ, cá được xiên que nướng trực tiếp trên lửa rơm mà không cần tẩm ướp cầu kỳ.</p><p>Thịt cá ngọt, thơm mùi khói, thường cuốn cùng bánh tráng, rau sống và chấm mắm me — hương vị dân dã khó quên của vùng đất Cà Mau.</p>',
        ],
    ];

    /** @var array<int, array{province: string, category: string, name: string, star: int, description: string, producer_name: string, ward_code: string}> */
    private const OCOP_PRODUCTS = [
        ['province' => 'hue', 'category' => 'food', 'name' => 'Mè xửng Huế', 'star' => 4, 'description' => 'Kẹo mè xửng dẻo thơm, đặc sản truyền thống lâu đời của xứ Huế.', 'producer_name' => 'Cơ sở mè xửng Thiên Hương', 'ward_code' => '19753'],
        ['province' => 'hue', 'category' => 'food', 'name' => 'Tôm chua Huế', 'star' => 3, 'description' => 'Tôm chua lên men chua ngọt, ăn kèm thịt luộc hoặc bún — món quà đặc trưng của Cố đô.', 'producer_name' => 'Cơ sở tôm chua Bà Duệ', 'ward_code' => '19774'],
        ['province' => 'hue', 'category' => 'food', 'name' => 'Trà cung đình Huế', 'star' => 3, 'description' => 'Trà thảo mộc pha chế theo bí quyết cung đình triều Nguyễn, thanh mát và bổ dưỡng.', 'producer_name' => 'Hợp tác xã trà cung đình Huế', 'ward_code' => '19777'],
        ['province' => 'hue', 'category' => 'craft', 'name' => 'Nón lá bài thơ Huế', 'star' => 4, 'description' => 'Nón lá mỏng nhẹ, soi dưới ánh sáng hiện rõ hình ảnh và câu thơ ẩn trong lòng nón.', 'producer_name' => 'Làng nghề nón lá Phú Cam', 'ward_code' => '19789'],
        ['province' => 'hue', 'category' => 'food', 'name' => 'Dầu tràm Huế', 'star' => 3, 'description' => 'Dầu tràm nguyên chất chưng cất thủ công, dùng giữ ấm và chăm sóc sức khỏe cho trẻ nhỏ.', 'producer_name' => 'Cơ sở dầu tràm Lộc Thủy', 'ward_code' => '19753'],

        ['province' => 'ca-mau', 'category' => 'food', 'name' => 'Tôm khô Cà Mau', 'star' => 4, 'description' => 'Tôm khô đất Mũi thịt chắc, ngọt tự nhiên, phơi nắng thủ công theo cách truyền thống.', 'producer_name' => 'Cơ sở tôm khô Đất Mũi', 'ward_code' => '31825'],
        ['province' => 'ca-mau', 'category' => 'food', 'name' => 'Cua Cà Mau', 'star' => 5, 'description' => 'Cua biển Cà Mau gạch đầy thịt chắc, đặc sản trứ danh vùng rừng ngập mặn.', 'producer_name' => 'Hợp tác xã nuôi cua Cà Mau', 'ward_code' => '31834'],
        ['province' => 'ca-mau', 'category' => 'food', 'name' => 'Mắm cá đồng Cà Mau', 'star' => 3, 'description' => 'Mắm cá đồng lên men tự nhiên, nguyên liệu chính làm nên món lẩu mắm trứ danh.', 'producer_name' => 'Cơ sở mắm Út Sáu', 'ward_code' => '31840'],
        ['province' => 'ca-mau', 'category' => 'food', 'name' => 'Muối Cà Mau', 'star' => 3, 'description' => 'Muối biển kết tinh tự nhiên từ vùng ven biển Cà Mau, hạt to, vị mặn thanh.', 'producer_name' => 'Hợp tác xã diêm dân Cà Mau', 'ward_code' => '31843'],
        ['province' => 'ca-mau', 'category' => 'food', 'name' => 'Khô cá kèo Cà Mau', 'star' => 3, 'description' => 'Cá kèo phơi khô nguyên con, nướng hoặc chiên đều giữ được vị ngọt đặc trưng.', 'producer_name' => 'Cơ sở khô Bảy Đông', 'ward_code' => '31825'],
    ];

    /** @var array<int, array<string, mixed>> */
    private const EVENTS = [
        [
            'province' => 'hue', 'title' => 'Festival Huế 2026', 'slug' => 'festival-hue-2026',
            'description' => 'Festival Huế quy tụ nhiều chương trình nghệ thuật, di sản và ẩm thực đặc sắc, tôn vinh văn hóa Cố đô.',
            'venue_name' => 'Đại Nội Huế', 'venue_address' => 'Phường Phú Xuân', 'ward_code' => '19753',
        ],
        [
            'province' => 'hue', 'title' => 'Lễ hội Áo dài Huế', 'slug' => 'le-hoi-ao-dai-hue',
            'description' => 'Sự kiện tôn vinh áo dài truyền thống gắn liền với hình ảnh người phụ nữ Huế, quy tụ nhiều nhà thiết kế trong nước.',
            'venue_name' => 'Cầu Trường Tiền', 'venue_address' => 'Phường Thuận Hóa', 'ward_code' => '19789',
        ],
        [
            'province' => 'ca-mau', 'title' => 'Lễ hội Nghinh Ông Sông Đốc', 'slug' => 'le-hoi-nghinh-ong-song-doc',
            'description' => 'Lễ hội truyền thống của ngư dân vùng biển Cà Mau, cầu mong mưa thuận gió hòa, tôm cá đầy khoang.',
            'venue_name' => 'Thị trấn Sông Đốc', 'venue_address' => 'Phường Bạc Liêu', 'ward_code' => '31825',
        ],
        [
            'province' => 'ca-mau', 'title' => 'Ngày hội Cua Cà Mau', 'slug' => 'ngay-hoi-cua-ca-mau',
            'description' => 'Sự kiện quảng bá thương hiệu cua Cà Mau, kết nối nông dân và doanh nghiệp thu mua, chế biến.',
            'venue_name' => 'Trung tâm Thương mại Cà Mau', 'venue_address' => 'Phường Vĩnh Trạch', 'ward_code' => '31834',
        ],
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
        Auth::login($creator);

        $articleCount = $this->seedArticles($creator, $editor, $head);
        $ocopCount    = $this->seedOcopProducts($creator);
        $eventCount   = $this->seedEvents($creator, $editor, $head);

        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info("  ✓ Province Showcase demo data seeded ({$articleCount} bài viết, {$ocopCount} sản phẩm OCOP, {$eventCount} sự kiện).");
    }

    private function provinceCode(string $province): string
    {
        return $province === 'hue' ? self::HUE_CODE : self::CA_MAU_CODE;
    }

    // ──────────────────────────────────────────────────────────────
    // ARTICLES (Di sản văn hóa / Ẩm thực vùng miền)
    // ──────────────────────────────────────────────────────────────

    private function seedArticles(User $creator, User $editor, User $head): int
    {
        $categoryIds = [
            'di-san-van-hoa'     => PostCategory::where('slug', 'di-san-van-hoa')->value('id'),
            'am-thuc-vung-mien'  => PostCategory::where('slug', 'am-thuc-vung-mien')->value('id'),
        ];

        if (! $categoryIds['di-san-van-hoa'] || ! $categoryIds['am-thuc-vung-mien']) {
            $this->command->warn('  ⚠ Thiếu category di-san-van-hoa/am-thuc-vung-mien — chạy ProvinceShowcaseCategorySeeder trước.');

            return 0;
        }

        $created = 0;
        foreach (self::ARTICLES as $def) {
            if (PostArticleTranslation::where('slug', $def['slug'])->exists()) {
                continue;
            }

            Auth::login($creator);

            $article = app(CreateArticleAction::class)->handle(ArticleData::from([
                'category_ids'            => [$categoryIds[$def['category']]],
                'is_primary_category_id'  => $categoryIds[$def['category']],
                'province_code'           => $this->provinceCode($def['province']),
            ]));

            $translation = app(CreateTranslationAction::class)->handle($article, 'vi', TranslationData::from([
                'title'   => $def['title'],
                'slug'    => $def['slug'],
                'excerpt' => $def['excerpt'],
            ]));

            app(SyncContentBlocksAction::class)->handle($translation, [
                ['type' => 'text', 'html' => $def['body']],
            ]);

            app(SubmitArticleForReviewAction::class)->handle($translation);

            Auth::login($editor);
            app(ApproveArticleTranslationAction::class)->handle($translation);

            Auth::login($head);
            app(PublishArticleAction::class)->handle($translation);

            $created++;
        }

        return $created;
    }

    // ──────────────────────────────────────────────────────────────
    // OCOP PRODUCTS
    // ──────────────────────────────────────────────────────────────

    private function seedOcopProducts(User $creator): int
    {
        Auth::login($creator);

        $categories = [
            'food'  => 'Đặc sản ẩm thực',
            'craft' => 'Thủ công mỹ nghệ',
        ];
        $categoryIds = [];
        foreach ($categories as $key => $name) {
            $category = OcopCategory::where('slug', Str::slug($name))->first();
            if (! $category) {
                $category = app(CreateOcopCategoryAction::class)->handle(OcopCategoryData::from(['name' => $name]));
            }
            $categoryIds[$key] = $category->id;
        }

        $created = 0;
        foreach (self::OCOP_PRODUCTS as $def) {
            $slug = Str::slug($def['name']);
            if (OcopProduct::where('slug', $slug)->exists()) {
                continue;
            }

            app(CreateOcopProductAction::class)->handle(OcopProductData::from([
                'category_id'       => $categoryIds[$def['category']],
                'name'              => $def['name'],
                'star_rating'       => $def['star'],
                'description'       => $def['description'],
                'province_code'     => $this->provinceCode($def['province']),
                'ward_code'         => $def['ward_code'],
                'producer_name'     => $def['producer_name'],
                'image_path'        => 'ocop/demo-' . $slug . '.jpg',
                'image_width'       => 800,
                'image_height'      => 800,
                'image_size_bytes'  => 120000,
                'status'            => 'published',
            ]));

            $created++;
        }

        return $created;
    }

    // ──────────────────────────────────────────────────────────────
    // EVENTS
    // ──────────────────────────────────────────────────────────────

    private function seedEvents(User $creator, User $editor, User $head): int
    {
        Auth::login($creator);

        $category = EventCategory::where('slug', 'le-hoi-van-hoa')->first();
        if (! $category) {
            $category = app(CreateEventCategoryAction::class)->handle(EventCategoryData::from(['name' => 'Lễ hội văn hóa']));
        }

        $created = 0;
        foreach (self::EVENTS as $i => $def) {
            if (Event::where('slug', $def['slug'])->exists()) {
                continue;
            }

            Auth::login($creator);

            $startOffset = 15 + ($i * 10);

            $event = app(CreateEventAction::class)->handle(EventData::from([
                'category_id'    => $category->id,
                'title'          => $def['title'] . ' (Demo)',
                'short_title'    => $def['title'],
                'description'    => $def['description'],
                'start_date'     => now()->addDays($startOffset)->toDateString(),
                'end_date'       => now()->addDays($startOffset + 1)->toDateString(),
                'start_time'     => '08:00',
                'end_time'       => '17:00',
                'location_type'  => 'physical',
                'venue_name'     => $def['venue_name'],
                'venue_address'  => $def['venue_address'],
                'province_code'  => $this->provinceCode($def['province']),
                'ward_code'      => $def['ward_code'],
                'website_url'    => 'https://familiesforlife.test/su-kien/' . Str::slug($def['title']),
                'price_type'     => 'free',
                // events.poster_path là cột NOT NULL (bắt buộc) — không thể để trống như
                // banners/OCOP images. Trước đây hardcode 'events/posters/{slug}.jpg' không có
                // file thật đi kèm → vỡ ảnh 404. Dùng lại 1 trong các poster thật đã có sẵn trên
                // disk (self::DEMO_POSTERS) thay vì bịa đường dẫn mới.
                'poster_path'    => self::DEMO_POSTERS[$i % count(self::DEMO_POSTERS)],
                'poster_alt'     => $def['title'],
                'is_featured'    => true,
            ]));

            $event->update(['slug' => $def['slug']]);

            Auth::login($editor);
            app(ApproveEventAction::class)->handle($event->fresh());

            Auth::login($head);
            app(PublishEventAction::class)->handle($event->fresh());

            $created++;
        }

        return $created;
    }
}
