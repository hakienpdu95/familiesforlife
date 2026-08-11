<?php

namespace Modules\Heritage\Database\Seeders;

use App\Models\User;
use App\Services\Media\MediaUploadService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Modules\Event\Models\Event;
use Modules\Heritage\Features\HeritageSiteManagement\Actions\CreateHeritageSiteAction;
use Modules\Heritage\Features\HeritageSiteManagement\Data\HeritageSiteData;
use Modules\Heritage\Models\HeritageSite;
use Modules\Ocop\Models\OcopProduct;
use Modules\Post\Models\PostArticleTranslation;

/**
 * spec/Heritage_Technical_Specification.md §9 — 5 HeritageSite demo cho Huế + Cà Mau (2 tỉnh đã
 * có chuyên đề, xem ProvinceShowcaseDemoSeeder), mỗi site có ảnh bìa thật, và "Đại Nội Huế" có đủ
 * cả 3 loại cross-link (Post qua pivot, Event qua heritage_site_id, OcopProduct qua
 * heritage_site_id) để trang chi tiết demo thấy được cả 3 khối liên quan cùng lúc.
 *
 * Dữ liệu Event/Ocop/Post đã tồn tại KHÔNG bị migrate/gán tự động ngoài các dòng liệt kê tường
 * minh dưới đây — chạy SAU ProvinceShowcaseDemoSeeder (cần Post/Ocop/Event demo tồn tại trước).
 * Idempotent — mỗi bản ghi dùng slug/tên cố định, bỏ qua nếu đã tồn tại.
 */
class HeritageDemoSeeder extends Seeder
{
    private const HUE_CODE = '46';

    private const CA_MAU_CODE = '96';

    /**
     * @var array<int, array{name: string, slug: string, heritage_type: string, rank: string, era: ?string, description: string, province: string, ward_code: ?string, is_featured: bool, article_slug: ?string, event_slug: ?string, ocop_name: ?string}>
     */
    private const SITES = [
        [
            'name' => 'Đại Nội Huế', 'slug' => 'dai-noi-hue',
            'heritage_type' => 'historical_monument', 'rank' => 'special_national',
            'era' => 'Thời Nguyễn (1802 - 1945)',
            'description' => 'Quần thể kiến trúc cung đình quy mô nhất còn lại của Việt Nam, gồm Hoàng thành và Tử Cấm thành, là trung tâm chính trị của triều Nguyễn suốt hơn 140 năm.',
            'province' => 'hue', 'ward_code' => '19753', 'is_featured' => true,
            'article_slug' => 'hue-dai-noi-hue', 'event_slug' => 'festival-hue-2026', 'ocop_name' => 'Trà cung đình Huế',
        ],
        [
            'name' => 'Chùa Thiên Mụ', 'slug' => 'chua-thien-mu',
            'heritage_type' => 'historical_monument', 'rank' => 'national',
            'era' => 'Năm 1601',
            'description' => 'Ngôi chùa cổ nhất Huế, nổi bật với tháp Phước Duyên bảy tầng soi bóng xuống sông Hương — hình ảnh gắn liền với Huế trong tâm thức nhiều thế hệ.',
            'province' => 'hue', 'ward_code' => null, 'is_featured' => false,
            'article_slug' => 'hue-chua-thien-mu', 'event_slug' => null, 'ocop_name' => null,
        ],
        [
            'name' => 'Lăng Tự Đức', 'slug' => 'lang-tu-duc',
            'heritage_type' => 'architectural_art', 'rank' => 'national',
            'era' => 'Thời Nguyễn (1867)',
            'description' => 'Lăng tẩm mang đậm dấu ấn thi ca của vua Tự Đức, thiết kế như một khu vườn thơ mộng với hồ nước, đình tạ và cây xanh — một trong những công trình đẹp nhất triều Nguyễn.',
            'province' => 'hue', 'ward_code' => null, 'is_featured' => true,
            'article_slug' => 'hue-lang-tu-duc', 'event_slug' => null, 'ocop_name' => null,
        ],
        [
            'name' => 'Mũi Cà Mau', 'slug' => 'mui-ca-mau',
            'heritage_type' => 'scenic_landscape', 'rank' => 'national',
            'era' => null,
            'description' => 'Điểm địa đầu cực Nam Tổ quốc, nơi du khách có thể chiêm ngưỡng cảnh mặt trời mọc ở biển Đông và lặn ở biển Tây chỉ trong cùng một ngày. Cột mốc tọa độ GPS 0001 là điểm check-in nổi tiếng.',
            'province' => 'ca-mau', 'ward_code' => '31825', 'is_featured' => true,
            'article_slug' => 'ca-mau-mui-ca-mau', 'event_slug' => null, 'ocop_name' => 'Cua Cà Mau',
        ],
        [
            'name' => 'Vườn quốc gia Mũi Cà Mau', 'slug' => 'vuon-quoc-gia-mui-ca-mau',
            'heritage_type' => 'scenic_landscape', 'rank' => 'national',
            'era' => null,
            'description' => 'Một trong những khu rừng ngập mặn lớn nhất Việt Nam, đóng vai trò quan trọng trong việc chắn sóng, chống sạt lở và điều hòa sinh thái vùng ven biển — được UNESCO công nhận là khu Ramsar.',
            'province' => 'ca-mau', 'ward_code' => null, 'is_featured' => false,
            'article_slug' => 'ca-mau-vuon-quoc-gia', 'event_slug' => 'ngay-hoi-cua-ca-mau', 'ocop_name' => null,
        ],
    ];

    public function run(): void
    {
        $creator = User::withoutGlobalScopes()->where('email', 'content-creator@system.local')->first();

        if (! $creator) {
            $this->command->warn('  ⚠ Thiếu tài khoản platform (content-creator@system.local) — chạy ApprovalDatabaseSeeder trước.');

            return;
        }

        $previousUser = Auth::user();
        Auth::login($creator);

        $created = 0;
        foreach (self::SITES as $def) {
            if (HeritageSite::where('slug', $def['slug'])->exists()) {
                continue;
            }

            $site = app(CreateHeritageSiteAction::class)->handle(HeritageSiteData::from([
                'name' => $def['name'],
                'slug' => $def['slug'],
                'heritage_type' => $def['heritage_type'],
                'rank' => $def['rank'],
                'era' => $def['era'],
                'description' => $def['description'],
                'province_code' => $def['province'] === 'hue' ? self::HUE_CODE : self::CA_MAU_CODE,
                'ward_code' => $def['ward_code'],
                'status' => 'published',
                'is_featured' => $def['is_featured'],
            ]));

            $this->attachCover($site);
            $this->linkRelatedContent($site, $def);

            $created++;
        }

        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info("  ✓ Heritage demo data seeded ({$created} di tích).");
    }

    /**
     * spec/Heritage_Technical_Specification.md §9 — mỗi site demo PHẢI có ảnh bìa thật (không để
     * trống, trang chi tiết lấy ảnh hero từ đây) — sinh 1 ảnh JPEG canvas màu đặc trưng qua
     * Intervention Image (không có ảnh tư liệu thật đi kèm repo, và addMediaFromUrl cần mạng
     * ngoài — không phù hợp môi trường seed offline).
     */
    private function attachCover(HeritageSite $site): void
    {
        $colors = ['#7c3aed', '#0891b2', '#b45309', '#15803d', '#be123c'];
        $color = $colors[$site->id % count($colors)];

        $canvas = Image::createImage(1200, 800);
        $canvas->fill($color);
        $encoded = $canvas->encode(new JpegEncoder(85));

        $tmpPath = sys_get_temp_dir().'/heritage-demo-'.$site->uuid.'.jpg';
        file_put_contents($tmpPath, (string) $encoded);

        $file = new UploadedFile($tmpPath, $site->slug.'.jpg', 'image/jpeg', null, true);

        app(MediaUploadService::class)->upload($file, $site, 'cover');

        @unlink($tmpPath);
    }

    /** @param array{article_slug: ?string, event_slug: ?string, ocop_name: ?string} $def */
    private function linkRelatedContent(HeritageSite $site, array $def): void
    {
        if ($def['article_slug']) {
            $article = PostArticleTranslation::where('slug', $def['article_slug'])->first()?->article;
            $article?->heritageSites()->syncWithoutDetaching([$site->id]);
        }

        if ($def['event_slug']) {
            Event::where('slug', $def['event_slug'])->update(['heritage_site_id' => $site->id]);
        }

        if ($def['ocop_name']) {
            OcopProduct::where('name', $def['ocop_name'])->update(['heritage_site_id' => $site->id]);
        }
    }
}
