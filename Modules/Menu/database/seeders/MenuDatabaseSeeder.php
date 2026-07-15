<?php

namespace Modules\Menu\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Event\Models\EventCategory;
use Modules\Menu\Enums\MenuLinkType;
use Modules\Menu\Models\MenuItem;
use Modules\Post\Models\PostCategory;

/**
 * Menu công khai (header + footer) cho cổng thông tin — cùng 1 cơ chế MenuItem::tree($location)
 * cho cả 2 vị trí (xem Modules\Menu\Providers\MenuServiceProvider — 2 view composer riêng cho
 * 'layouts.partials.frontend-header' và 'layouts.partials.frontend-footer').
 *
 * - header: cấu trúc theo spec/header.html (mega-menu 2 cấp: vài mục nhóm nhiều danh mục con
 *   qua dropdown, vài mục link thẳng 1 danh mục, kết ở 1 nhóm "Sự kiện gia đình").
 * - footer: cấu trúc theo spec/footer.html (3 nhóm cột — mỗi nhóm là 1 MenuItem link_type=none
 *   làm tiêu đề cột (".nav-footer__title") + children làm link phẳng (".nav-footer__list"),
 *   ĐÚNG pattern nhóm dropdown đã dùng ở header — xem frontend-footer.blade.php: nhóm CUỐI
 *   cùng (sort_order lớn nhất) luôn render thành thanh link pháp lý cuối trang
 *   (".nav-siteinfo"), các nhóm còn lại render thành cột (".nav-footer__content")).
 *
 * Nội dung dùng đúng category/thương hiệu của familiesforlife (KHÔNG copy nhãn/link của site
 * tham khảo — xem Modules\Post\Database\Seeders\PostDemoSeeder / Modules\Event\Database\
 * Seeders\EventDemoSeeder cho danh sách category thật đã seed). Link liên hệ/pháp lý dùng
 * placeholder (mailto nội bộ hoặc '#') vì app chưa có trang Liên hệ/Chính sách/Điều khoản thật.
 *
 * Idempotent theo (location, link_type, category_id) hoặc (location, link_type, url) — mỗi
 * mục demo dùng khoá cố định, bỏ qua nếu đã tồn tại. Chạy SAU PostDemoSeeder/EventDemoSeeder
 * (cần category_id thật) — xem database/seeders/SystemDataSeeder.php.
 */
class MenuDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::withoutGlobalScopes()->role('super-admin')->orderBy('id')->value('id');

        if ($userId === null) {
            $this->command->warn('  ⚠ Không tìm thấy tài khoản super-admin — chạy AuthDatabaseSeeder trước.');

            return;
        }

        $created = 0;

        // ── 1. Nuôi dạy con & Giáo dục (nhóm — dropdown 3 mục) ───────────────
        $created += $this->seedGroup($userId, 'Nuôi dạy con & Giáo dục', 10, [
            ['Nuôi dạy con', 'Nuôi dạy con'],
            ['Giáo dục', 'Giáo dục'],
            ['Kỹ năng sống', 'Kỹ năng sống'],
        ]);

        // ── 2. Sức khỏe & Dinh dưỡng (nhóm — dropdown 2 mục) ─────────────────
        $created += $this->seedGroup($userId, 'Sức khỏe & Dinh dưỡng', 20, [
            ['Sức khỏe gia đình', 'Sức khỏe gia đình'],
            ['Dinh dưỡng', 'Dinh dưỡng'],
        ]);

        // ── 3-5. Mục đơn — link thẳng 1 danh mục, không dropdown ─────────────
        $created += $this->seedCategoryLink($userId, 'Hôn nhân & Gia đình', 'Hôn nhân & Gia đình', 30);
        $created += $this->seedCategoryLink($userId, 'Tài chính gia đình', 'Tài chính gia đình', 40);
        $created += $this->seedCategoryLink($userId, 'Du lịch gia đình', 'Du lịch gia đình', 50);

        // ── 6. Sự kiện gia đình (nhóm — dropdown 4 mục, link_type=url vì Event
        // không dùng chung taxonomy PostCategory — xem MenuItem::resolveUrl()) ──
        $created += $this->seedEventGroup($userId);

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

    /** @param array<int, array{0: string, 1: string}> $children [label, category name][] */
    private function seedGroup(int $userId, string $label, int $sortOrder, array $children): int
    {
        $created = 0;

        $parent = MenuItem::where('location', 'header')
            ->where('link_type', MenuLinkType::None)
            ->where('label', $label)
            ->first();

        if (! $parent) {
            $parent = MenuItem::create([
                'location'   => 'header',
                'link_type'  => MenuLinkType::None,
                'label'      => $label,
                'sort_order' => $sortOrder,
                'depth'      => 0,
                'is_active'  => true,
                'created_by' => $userId,
            ]);
            $created++;
        }

        foreach ($children as $childSortOrder => [$childLabel, $categoryName]) {
            $categoryId = PostCategory::where('name', $categoryName)->value('id');

            if (! $categoryId) {
                $this->command->warn("  ⚠ Không tìm thấy PostCategory \"{$categoryName}\" — bỏ qua mục \"{$childLabel}\".");

                continue;
            }

            $exists = MenuItem::where('location', 'header')
                ->where('parent_id', $parent->id)
                ->where('category_id', $categoryId)
                ->exists();

            if ($exists) {
                continue;
            }

            MenuItem::create([
                'location'    => 'header',
                'parent_id'   => $parent->id,
                'link_type'   => MenuLinkType::Category,
                'category_id' => $categoryId,
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

    private function seedCategoryLink(int $userId, string $label, string $categoryName, int $sortOrder): int
    {
        $categoryId = PostCategory::where('name', $categoryName)->value('id');

        if (! $categoryId) {
            $this->command->warn("  ⚠ Không tìm thấy PostCategory \"{$categoryName}\" — bỏ qua mục \"{$label}\".");

            return 0;
        }

        $exists = MenuItem::where('location', 'header')
            ->whereNull('parent_id')
            ->where('category_id', $categoryId)
            ->exists();

        if ($exists) {
            return 0;
        }

        MenuItem::create([
            'location'    => 'header',
            'link_type'   => MenuLinkType::Category,
            'category_id' => $categoryId,
            'label'       => $label,
            'sort_order'  => $sortOrder,
            'depth'       => 0,
            'is_active'   => true,
            'created_by'  => $userId,
        ]);

        return 1;
    }

    private function seedEventGroup(int $userId): int
    {
        $created = 0;
        $label   = 'Sự kiện gia đình';

        $parent = MenuItem::where('location', 'header')
            ->where('link_type', MenuLinkType::None)
            ->where('label', $label)
            ->first();

        if (! $parent) {
            $parent = MenuItem::create([
                'location'   => 'header',
                'link_type'  => MenuLinkType::None,
                'label'      => $label,
                'sort_order' => 60,
                'depth'      => 0,
                'is_active'  => true,
                'created_by' => $userId,
            ]);
            $created++;
        }

        $links = [
            ['Tất cả sự kiện', route('event.public.home')],
            ['Hội thảo phụ huynh', $this->eventCategoryUrl('Hội thảo phụ huynh')],
            ['Ngày hội gia đình', $this->eventCategoryUrl('Ngày hội gia đình')],
            ['Trại hè thiếu nhi', $this->eventCategoryUrl('Trại hè thiếu nhi')],
        ];

        foreach ($links as $childSortOrder => [$childLabel, $url]) {
            if (! $url) {
                $this->command->warn("  ⚠ Không tìm thấy EventCategory cho mục \"{$childLabel}\" — bỏ qua.");

                continue;
            }

            $exists = MenuItem::where('location', 'header')
                ->where('parent_id', $parent->id)
                ->where('link_type', MenuLinkType::Url)
                ->where('url', $url)
                ->exists();

            if ($exists) {
                continue;
            }

            MenuItem::create([
                'location'   => 'header',
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

    private function eventCategoryUrl(string $categoryName): ?string
    {
        $slug = EventCategory::where('name', $categoryName)->value('slug');

        return $slug ? route('event.public.category', ['category' => $slug]) : null;
    }
}
