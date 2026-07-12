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
use Modules\Product\Database\Seeders\ProductDatabaseSeeder;
use Modules\Post\Database\Seeders\PostDatabaseSeeder;
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

            // ── 25. Post: permissions (post_article.*/post_category.*) — bài viết + Product CTA Box ──
            PostDatabaseSeeder::class,

            // ── 26. Approval: permission approval.view_dashboard (duy nhất thuộc module này) ──
            ApprovalDatabaseSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('  ✓ Tất cả dữ liệu mặc định đã được seed thành công.');
        $this->command->newLine();
    }
}
