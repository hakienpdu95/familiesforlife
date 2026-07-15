<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ActivityLog\Database\Seeders\ActivityLogPermissionsSeeder;
use Modules\Approval\Database\Seeders\ApprovalDatabaseSeeder;
use Modules\Assessment\Database\Seeders\AssessmentDatabaseSeeder;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Lead\Database\Seeders\LeadDatabaseSeeder;
use Modules\LeadPipelineStage\Database\Seeders\LeadPipelineStageSeeder;
use Modules\LeadSource\Database\Seeders\LeadSourceSeeder;
use Modules\Aicem\Database\Seeders\AicemDatabaseSeeder;
use Modules\Banner\Database\Seeders\BannerDatabaseSeeder;
use Modules\Event\Database\Seeders\EventDatabaseSeeder;
use Modules\Event\Database\Seeders\EventDemoSeeder;
use Modules\Menu\Database\Seeders\MenuDatabaseSeeder;
use Modules\Product\Database\Seeders\ProductDatabaseSeeder;
use Modules\Post\Database\Seeders\PostDatabaseSeeder;
use Modules\Post\Database\Seeders\PostDemoSeeder;
use Modules\Organization\Database\Seeders\OrganizationRolePermissionSeeder;
use Modules\Subscription\Database\Seeders\SubscriptionDatabaseSeeder;
use Modules\Survey\Database\Seeders\SurveyDatabaseSeeder;

/**
 * Master Seeder — điểm khởi chạy duy nhất cho toàn bộ dữ liệu mặc định hệ thống.
 *
 * Lệnh chạy:
 *   php artisan db:seed
 *   php artisan db:seed --class=Database\\Seeders\\SystemDataSeeder
 *
 * Không bao gồm:
 *   - OrganizationDemoSeeder (1000 orgs demo — chỉ chạy thủ công khi cần)
 *   - Các seeder rỗng (Employee, Customer, Branch, Department, Project...)
 */
class SystemDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->newLine();
        $this->command->info('┌──────────────────────────────────────────┐');
        $this->command->info('│       SystemDataSeeder — starting...     │');
        $this->command->info('└──────────────────────────────────────────┘');
        $this->command->newLine();

        $this->call([
            // ── 1. IAM: 8 tenant roles + 40+ permissions ─────────────────
            RolePermissionSeeder::class,

            // ── 2. Additional module permissions (cần roles tồn tại trước)
            ActivityLogPermissionsSeeder::class,

            // ── 3. Super-admin role + 2 tài khoản hệ thống ───────────────
            AuthDatabaseSeeder::class,

            // ── 4. Template roles cấp org (owner/admin/manager/member) ────
            OrganizationRolePermissionSeeder::class,

            // ── 5. Org hệ thống mặc định (id=1 trên fresh DB) ────────────
            SystemOrganizationSeeder::class,

            // ── 6. Demo organization (dev/test) ───────────────────────────
            OrganizationSeeder::class,

            // ── 7. Test users (1 per role) ────────────────────────────────
            UserSeeder::class,

            // ── 8. Subscription plans + features + gán starter cho orgs ──
            SubscriptionDatabaseSeeder::class,

            // ── 9. Danh mục dùng chung (global master data) ───────────────
            LeadPipelineStageSeeder::class,   // global template stages (org_id = null)
            LeadSourceSeeder::class,           // global template sources (org_id = null)

            // ── 10. Module Lead: stages + sources cho demo org ────────────
            LeadDatabaseSeeder::class,

            // ── 13. Assessment: TDWCF, 5-Pillar, certifications, sandbox ──
            AssessmentDatabaseSeeder::class,

            // ── 14. Survey: permissions, AI Readiness, scoring config ──────
            SurveyDatabaseSeeder::class,

            // ── 24. Product: permissions (product.*/product_category.*) — catalog cho Post CTA Box ──
            ProductDatabaseSeeder::class,

            // ── 24b. Aicem: permissions + platform-editorial org + default AI workflow — bắt buộc
            // TRƯỚC Post, vì PublishArticleAction bắn event ArticlePublished mà
            // SuggestExampleGoodFromPublishedArticle (listener của Aicem) cần org này tồn tại ──
            AicemDatabaseSeeder::class,

            // ── 25. Post: permissions (post_article.*/post_category.*) — bài viết + Product CTA Box ──
            PostDatabaseSeeder::class,

            // ── 26. Event: permissions (event.*/event_category.*) — sự kiện nền tảng ──
            EventDatabaseSeeder::class,

            // ── 27. Approval: permission approval.view_dashboard + 3 tài khoản platform
            // (content-creator/editor/content-head@system.local) — PostDemoSeeder/EventDemoSeeder
            // cần các tài khoản này để chạy Action create→submit→approve→publish thật ──
            ApprovalDatabaseSeeder::class,

            // ── 27b. Banner: permission banner.manage — gán cho platform_ops/platform_content_head
            // (role do ApprovalDatabaseSeeder tạo ở bước 27), nên PHẢI đứng sau bước đó ──
            BannerDatabaseSeeder::class,

            // ── 28. Demo content: bài viết + sự kiện mẫu đã xuất bản (đọc cho trang public) ──
            PostDemoSeeder::class,
            EventDemoSeeder::class,

            // ── 29. Menu (header): mega-menu công khai — cần category_id thật (bước 28) ──
            MenuDatabaseSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('  ✓ Tất cả dữ liệu mặc định đã được seed thành công.');
        $this->command->newLine();
    }
}
