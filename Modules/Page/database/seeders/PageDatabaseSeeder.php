<?php

namespace Modules\Page\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Page\Models\Page;

/**
 * spec/Page_Static_Pages_Technical_Specification.md §6 — 4 trang mặc định, is_system=true
 * (không thể xoá), status=draft (Admin phải tự điền nội dung thật và xuất bản — seeder KHÔNG
 * tự publish, tránh trang rỗng lên public). "Giới thiệu"/"Liên hệ" dùng template riêng
 * (about/contact) — KHÔNG được publish trước khi dev tạo view tương ứng (PublishPageAction
 * tự chặn kỹ thuật, không chỉ dựa kỷ luật vận hành — xem §3.3, Phase 5a).
 *
 * Idempotent qua firstOrCreate theo slug — chạy lại không tạo trùng.
 */
class PageDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PagePermissionSeeder::class,
        ]);

        $userId = User::withoutGlobalScopes()->role('super-admin')->orderBy('id')->value('id');

        if ($userId === null) {
            $this->command->warn('  ⚠ Không tìm thấy tài khoản super-admin — chạy AuthDatabaseSeeder trước.');

            return;
        }

        $pages = [
            ['title' => 'Giới thiệu', 'slug' => 'gioi-thieu', 'template' => 'about'],
            ['title' => 'Liên hệ', 'slug' => 'lien-he', 'template' => 'contact'],
            ['title' => 'Điều khoản sử dụng', 'slug' => 'dieu-khoan-su-dung', 'template' => 'default'],
            ['title' => 'Chính sách bảo mật', 'slug' => 'chinh-sach-bao-mat', 'template' => 'default'],
        ];

        $created = 0;

        foreach ($pages as $attrs) {
            $page = Page::firstOrCreate(
                ['slug' => $attrs['slug']],
                [
                    'title'      => $attrs['title'],
                    'template'   => $attrs['template'],
                    'is_system'  => true,
                    'created_by' => $userId,
                ]
            );

            if ($page->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("  ✓ Page (trang tĩnh mặc định) seeded ({$created} trang mới).");
    }
}
