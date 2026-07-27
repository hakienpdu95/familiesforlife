<?php

namespace Modules\Menu\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Modules\Menu\Enums\MenuLinkType;
use Modules\Menu\Models\MenuItem;
use Modules\Post\Features\CategoryManagement\Actions\CreateCategoryAction;
use Modules\Post\Features\CategoryManagement\Data\CategoryData;
use Modules\Post\Models\PostCategory;

/**
 * Menu công khai (header + footer) cho cổng thông tin — cùng 1 cơ chế MenuItem::tree($location)
 * cho cả 2 vị trí (xem Modules\Menu\Providers\MenuServiceProvider — 2 view composer riêng cho
 * 'layouts.partials.frontend-header' và 'layouts.partials.frontend-footer').
 *
 * - header (v2, 2026-07-27): thay hẳn taxonomy cũ (Nuôi dạy con/Sức khỏe/Hôn nhân/Tài chính/
 *   Du lịch/Sự kiện — link category/event thật) bằng cấu trúc theo GIAI ĐOẠN PHÁT TRIỂN CỦA TRẺ
 *   (Pregnancy → Babies → Toddler & Kids → Family → School Visit → Product & Service, yêu cầu
 *   thực tế của người phụ trách nội dung). Mục con/cháu dùng `link_type=category` link tới
 *   PostCategory THẬT (tạo mới nếu chưa có — xem seedCategory()), KHÔNG phải `link_type=url` với
 *   slug tự chế như bản đầu (v2.0) của đợt thay đổi này — bản v2.0 sinh URL kiểu
 *   "/mang-thai/chuan-bi-mang-thai" không khớp route `danh-muc/{category:slug}` (post.public.
 *   category) thật của site nên MỌI mục lá đều 404 khi bấm thử trên site — route dùng model
 *   binding nên chỉ hết 404 khi slug đó THẬT SỰ tồn tại trong bảng `post_categories`. Babies/
 *   Toddler & Kids: giai đoạn tuổi (VD "Sơ sinh 0-3 tháng") cũng là 1 category CHA thật, mục lá
 *   là category CON (`parent_id` trỏ về category cha đó) — cùng kiểu cây cha/con category 7→9,10
 *   ("Du lịch gia đình" → "Di sản văn hóa"/"Ẩm thực vùng miền") đã có sẵn trong DB.
 *   `seedGroup()`/`seedCategoryLink()`/`seedEventGroup()` (link category/event thật) của bản v1
 *   đã bị GỠ BỎ cùng đợt này — không còn chỗ dùng vì không nhóm nào trong cấu trúc mới link
 *   category/event có sẵn theo đúng tên cũ.
 * - footer: cấu trúc theo spec/footer.html (3 nhóm cột — mỗi nhóm là 1 MenuItem link_type=none
 *   làm tiêu đề cột (".nav-footer__title") + children làm link phẳng (".nav-footer__list"),
 *   ĐÚNG pattern nhóm dropdown đã dùng ở header — xem frontend-footer.blade.php: nhóm CUỐI
 *   cùng (sort_order lớn nhất) luôn render thành thanh link pháp lý cuối trang
 *   (".nav-siteinfo"), các nhóm còn lại render thành cột (".nav-footer__content")).
 *
 * Header KHÔNG idempotent theo kiểu "khoá cố định, bỏ qua nếu đã tồn tại" như footer bên dưới —
 * xoá SẠCH (`forceDelete()`, không phải soft-delete — model dùng SoftDeletes nên `cascadeOnDelete()`
 * khai báo ở migration KHÔNG tự kích hoạt khi soft-delete, phải xoá thật để dọn theo tầng) toàn bộ
 * `location=header` rồi tạo lại từ đầu mỗi lần chạy — chủ đích để mỗi lần seed luôn phản ánh ĐÚNG
 * cấu trúc mới nhất, không cần so khớp khoá idempotent phức tạp với dữ liệu demo sẽ bị thay hết.
 * Footer vẫn giữ nguyên idempotent theo (location, link_type, category_id)/(location, link_type,
 * url) như trước — chạy SAU PostDemoSeeder/EventDemoSeeder (footer's "Câu chuyện của chúng tôi"
 * dùng route thật) — xem database/seeders/SystemDataSeeder.php.
 */
class MenuDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::withoutGlobalScopes()->role('super-admin')->orderBy('id')->first();

        if ($admin === null) {
            $this->command->warn('  ⚠ Không tìm thấy tài khoản super-admin — chạy AuthDatabaseSeeder trước.');

            return;
        }

        $userId = $admin->id;

        // Xoá SẠCH toàn bộ header cũ trước khi tạo lại — xem docblock lớp (KHÔNG idempotent
        // theo khoá, luôn thay hoàn toàn). forceDelete() (không phải delete()) vì cascadeOnDelete()
        // khai báo ở migration chỉ kích hoạt với DELETE thật, không kích hoạt khi model soft-delete
        // (chỉ UPDATE deleted_at) — dùng delete() thường sẽ để sót children mồ côi.
        MenuItem::withTrashed()->where('location', 'header')->forceDelete();

        // CreateCategoryAction (gọi trong seedCategory()) lấy created_by qua auth()->id() (không
        // nhận tham số) — cùng convention Modules\Post\Database\Seeders\PostDemoSeeder::run(),
        // đăng nhập tạm bằng super-admin rồi khôi phục lại user đang đăng nhập trước đó (nếu có)
        // sau khi xong, không được để "rò rỉ" phiên đăng nhập của seeder ra ngoài.
        $previousUser = Auth::user();
        Auth::login($admin);

        $created = 0;

        // ── 1. Pregnancy - Mang thai (nhóm — dropdown 5 mục phẳng) ───────────
        $created += $this->seedFlatUrlGroup($userId, 'Mang thai', 10, [
            'Chuẩn bị mang thai',
            'Sự phát triển của thai nhi',
            'Sức khỏe bà bầu',
            'Thực phẩm và dinh dưỡng',
            'Chuẩn bị sinh nở',
        ]);

        // ── 2. Babies - Em bé (nhóm 3 cấp — 2 giai đoạn con, mỗi giai đoạn có mục lá riêng) ──
        $created += $this->seedNestedUrlGroup($userId, 'Em bé', 20, [
            'Sơ sinh 0-3 tháng' => [
                'Chăm sóc trẻ sơ sinh',
                'Phát triển trẻ sơ sinh',
                'Bệnh thường gặp & phòng ngừa',
                'Sữa mẹ và cho con bú',
                'Thực phẩm và dinh dưỡng',
            ],
            'Trẻ nhỏ 3 tháng - 1 tuổi' => [
                'Chăm sóc trẻ nhỏ',
                'Phát triển trẻ nhỏ',
                'Bệnh thường gặp & phòng ngừa',
                'Thực phẩm và dinh dưỡng',
            ],
        ]);

        // ── 3. Toddler & Kids - Trẻ chập chững & Trẻ em (nhóm 3 cấp — 3 giai đoạn con) ──
        $created += $this->seedNestedUrlGroup($userId, 'Trẻ chập chững & Trẻ em', 30, [
            'Trẻ chập chững 1-3 tuổi' => [
                'Chăm sóc trẻ chập chững',
                'Phát triển trẻ chập chững',
                'Bệnh thường gặp & phòng ngừa',
                'Thực phẩm và dinh dưỡng',
            ],
            'Trẻ mẫu giáo 3-6 tuổi' => [
                'Chăm sóc trẻ mẫu giáo',
                'Phát triển trẻ mẫu giáo',
                'Bệnh thường gặp & phòng ngừa',
                'Thực phẩm và dinh dưỡng',
            ],
            'Trẻ tiểu học 6-12 tuổi' => [
                'Chăm sóc trẻ tiểu học',
                'Phát triển trẻ tiểu học',
                'Bệnh thường gặp & phòng ngừa',
                'Thực phẩm và dinh dưỡng',
            ],
        ]);

        // ── 4. Family - Gia đình (nhóm — dropdown 6 mục phẳng) ───────────────
        $created += $this->seedFlatUrlGroup($userId, 'Gia đình', 40, [
            'Sức khỏe cha mẹ',
            'Mối quan hệ gia đình',
            'Hoạt động ngoài trời',
            'Chăm sóc nhà cửa',
            'Tài chính gia đình',
            'Quyền lợi và pháp lý',
        ]);

        // ── 5. School Visit - Lựa chọn trường học (nhóm — dropdown 4 mục phẳng) ──
        $created += $this->seedFlatUrlGroup($userId, 'Lựa chọn trường học', 50, [
            'Trường mầm non và tiểu học',
            'Trường nâng cao kỹ năng',
            'Trung tâm học tập',
            'Giáo dục tại nhà',
        ]);

        // ── 6. Product & Service - Video & Giải thưởng (nhóm — dropdown 3 mục phẳng) ──
        $created += $this->seedFlatUrlGroup($userId, 'Video & Giải thưởng', 60, [
            'Sản phẩm và Dịch vụ',
            'Video',
            'Giải thưởng nổi bật',
        ]);

        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info("  ✓ Menu (header) demo data seeded ({$created} mục mới).");

        $createdFooter = 0;

        // ── 1. Kết nối (nhóm — cột "nav-footer__content") ────────────────────
        $createdFooter += $this->seedFooterGroup($userId, 'Kết nối', 10, [
            ['Liên hệ', 'mailto:lienhe@viagiadinh.test'],
            ['Hợp tác quảng cáo', 'mailto:hoptac@viagiadinh.test'],
            ['Góp ý nội dung', 'mailto:gopy@viagiadinh.test'],
        ]);

        // ── 2. Về chúng tôi (nhóm — cột "nav-footer__content") ───────────────
        $createdFooter += $this->seedFooterGroup($userId, 'Về chúng tôi', 20, [
            ['Câu chuyện của chúng tôi', route('post.public.home')],
        ]);

        // ── 3. Pháp lý (sort_order CAO NHẤT — frontend-footer.blade.php render nhóm cuối
        // cùng này thành thanh link pháp lý cuối trang thay vì 1 cột, khớp spec/footer.html
        // ".nav-siteinfo"). Chưa có trang Chính sách/Điều khoản thật nên dùng '#' placeholder. ──
        $createdFooter += $this->seedFooterGroup($userId, 'Pháp lý', 30, [
            ['Chính sách bảo mật', '#'],
            ['Khả năng tiếp cận', '#'],
            ['Điều khoản sử dụng', '#'],
            ['Về quảng cáo của chúng tôi', '#'],
            ['Không bán thông tin của tôi', '#'],
            ['Thuật ngữ', '#'],
        ]);

        $this->command->info("  ✓ Menu (footer) demo data seeded ({$createdFooter} mục mới).");
    }

    /** @param array<int, array{0: string, 1: string}> $links [label, url][] */
    private function seedFooterGroup(int $userId, string $label, int $sortOrder, array $links): int
    {
        $created = 0;

        $parent = MenuItem::where('location', 'footer')
            ->where('link_type', MenuLinkType::None)
            ->where('label', $label)
            ->first();

        if (! $parent) {
            $parent = MenuItem::create([
                'location'   => 'footer',
                'link_type'  => MenuLinkType::None,
                'label'      => $label,
                'sort_order' => $sortOrder,
                'depth'      => 0,
                'is_active'  => true,
                'created_by' => $userId,
            ]);
            $created++;
        }

        foreach ($links as $childSortOrder => [$childLabel, $url]) {
            // Khoá theo CẢ url lẫn label — nhóm "Pháp lý" dùng chung placeholder '#' cho nhiều
            // link (chưa có trang Chính sách/Điều khoản thật), chỉ khoá theo url sẽ khiến các
            // mục sau bị coi là trùng lặp (đã bỏ qua) dù label khác nhau.
            $exists = MenuItem::where('location', 'footer')
                ->where('parent_id', $parent->id)
                ->where('link_type', MenuLinkType::Url)
                ->where('url', $url)
                ->where('label', $childLabel)
                ->exists();

            if ($exists) {
                continue;
            }

            MenuItem::create([
                'location'   => 'footer',
                'parent_id'  => $parent->id,
                'link_type'  => MenuLinkType::Url,
                'url'        => $url,
                'label'      => $childLabel,
                'sort_order' => $childSortOrder,
                'depth'      => 1,
                'is_active'  => true,
                'created_by' => $userId,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Nhóm header 2 cấp (cha `link_type=none` + con phẳng `link_type=category`, mỗi con link tới
     * 1 PostCategory THẬT — xem seedCategory()). Không cần check tồn tại trước khi tạo MenuItem
     * (không idempotent theo khoá) vì `run()` đã `forceDelete()` sạch header trước khi gọi hàm
     * này — nhưng PostCategory bên dưới VẪN idempotent (seedCategory() tái sử dụng category đã
     * có cùng tên/parent thay vì tạo trùng).
     *
     * @param  string[]  $children  Nhãn các mục con (depth=1)
     */
    private function seedFlatUrlGroup(int $userId, string $label, int $sortOrder, array $children): int
    {
        $parent = MenuItem::create([
            'location'   => 'header',
            'link_type'  => MenuLinkType::None,
            'label'      => $label,
            'sort_order' => $sortOrder,
            'depth'      => 0,
            'is_active'  => true,
            'created_by' => $userId,
        ]);

        $created = 1;

        foreach ($children as $childSortOrder => $childLabel) {
            MenuItem::create([
                'location'    => 'header',
                'parent_id'   => $parent->id,
                'link_type'   => MenuLinkType::Category,
                'category_id' => $this->seedCategory($childLabel),
                'label'       => $childLabel,
                'sort_order'  => $childSortOrder,
                'depth'       => 1,
                'is_active'   => true,
                'created_by'  => $userId,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Nhóm header 3 cấp (cha `link_type=none` > giai đoạn con > mục lá) — dùng cho "Babies"/
     * "Toddler & Kids" (mỗi giai đoạn tuổi có mục lá riêng, không thể phẳng thành 2 cấp như
     * seedFlatUrlGroup()). `max_depth` config (Modules/Menu/config/config.php) = 2 → depth 0/1/2
     * hợp lệ, đúng khớp 3 cấp này.
     *
     * Giai đoạn tuổi (depth=1) VÀ mục lá (depth=2) đều `link_type=category` link PostCategory
     * THẬT — giai đoạn tuổi là category CHA, mục lá là category CON của nó (`parent_id` trỏ về
     * category cha, xem seedCategory()) — cùng kiểu cây category 7→9,10 đã có sẵn trong DB, khác
     * hẳn seedFlatUrlGroup() (mọi mục con đều category gốc, không category cha nào chung).
     *
     * @param  array<string, string[]>  $subgroups  Nhãn giai đoạn (depth=1) => nhãn các mục lá (depth=2)
     */
    private function seedNestedUrlGroup(int $userId, string $label, int $sortOrder, array $subgroups): int
    {
        $parent = MenuItem::create([
            'location'   => 'header',
            'link_type'  => MenuLinkType::None,
            'label'      => $label,
            'sort_order' => $sortOrder,
            'depth'      => 0,
            'is_active'  => true,
            'created_by' => $userId,
        ]);

        $created      = 1;
        $subSortOrder = 0;

        foreach ($subgroups as $subLabel => $leaves) {
            $subCategoryId = $this->seedCategory($subLabel);

            $subGroup = MenuItem::create([
                'location'    => 'header',
                'parent_id'   => $parent->id,
                'link_type'   => MenuLinkType::Category,
                'category_id' => $subCategoryId,
                'label'       => $subLabel,
                'sort_order'  => $subSortOrder++,
                'depth'       => 1,
                'is_active'   => true,
                'created_by'  => $userId,
            ]);
            $created++;

            foreach ($leaves as $leafSortOrder => $leafLabel) {
                MenuItem::create([
                    'location'    => 'header',
                    'parent_id'   => $subGroup->id,
                    'link_type'   => MenuLinkType::Category,
                    'category_id' => $this->seedCategory($leafLabel, $subCategoryId),
                    'label'       => $leafLabel,
                    'sort_order'  => $leafSortOrder,
                    'depth'       => 2,
                    'is_active'   => true,
                    'created_by'  => $userId,
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Tạo (hoặc tái sử dụng) 1 PostCategory THẬT cho mục menu — idempotent theo (name, parent_id),
     * KHÔNG theo slug (nhiều nhóm dùng chung nhãn "Thực phẩm và dinh dưỡng"/"Bệnh thường gặp &
     * phòng ngừa" cho các giai đoạn tuổi KHÁC NHAU — đây là các category khác nhau về ngữ nghĩa dù
     * trùng tên hiển thị, chỉ khác `parent_id`; so theo slug sẽ khiến chúng bị coi là 1 category
     * DUY NHẤT do slug đầu tiên trùng tên được tái sử dụng nhầm cho mọi giai đoạn khác). Nếu tên +
     * parent này đã khớp 1 category có sẵn (VD "Tài chính gia đình" — đã tồn tại từ trước, seed
     * bởi PostDemoSeeder) thì tái dùng luôn, không tạo trùng.
     *
     * `CreateCategoryAction` tự sinh slug unique (thêm hậu tố -2, -3... khi trùng — xem
     * Modules\Post\Features\CategoryManagement\Actions\CreateCategoryAction::uniqueSlug()) và lấy
     * `created_by` qua `auth()->id()` — cần `Auth::login()` trước khi gọi (xem run()).
     */
    private function seedCategory(string $name, ?int $parentId = null): int
    {
        $category = PostCategory::where('name', $name)->where('parent_id', $parentId)->first();

        if ($category) {
            return $category->id;
        }

        return app(CreateCategoryAction::class)->handle(CategoryData::from([
            'name'      => $name,
            'parent_id' => $parentId,
        ]))->id;
    }
}
