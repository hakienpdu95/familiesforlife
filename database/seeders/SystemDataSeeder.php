<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ActivityLog\Database\Seeders\ActivityLogPermissionsSeeder;
use Modules\AiCopilot\Database\Seeders\AiCopilotDatabaseSeeder;
use Modules\Assessment\Database\Seeders\AssessmentDatabaseSeeder;
use Modules\Assessment\Database\Seeders\MarketplaceDemoSeeder;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Deployment\Database\Seeders\DataCollectionV1Seeder;
use Modules\Deployment\Database\Seeders\DefaultVerticalTemplateSeeder;
use Modules\Deployment\Database\Seeders\DeploymentEnginePermissionSeeder;
use Modules\Deployment\Database\Seeders\HtxTienDuongDemoSeeder;
use Modules\Deployment\Database\Seeders\ReadinessV1SurveySeeder;
use Modules\JobPosting\Database\Seeders\JobPostingDatabaseSeeder;
use Modules\JobTitle\Database\Seeders\JobTitleDatabaseSeeder;
use Modules\Lead\Database\Seeders\LeadDatabaseSeeder;
use Modules\LeadPipelineStage\Database\Seeders\LeadPipelineStageSeeder;
use Modules\LeadSource\Database\Seeders\LeadSourceSeeder;
use Modules\BusinessBlueprint\Database\Seeders\BusinessBlueprintDatabaseSeeder;
use Modules\BusinessSolution\Database\Seeders\BusinessSolutionDatabaseSeeder;
use Modules\OcopRubric\Database\Seeders\OcopRubricDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationRolePermissionSeeder;
use Modules\OrganizationSolution\Database\Seeders\OrganizationSolutionDatabaseSeeder;
use Modules\Recruitment\Database\Seeders\RecruitmentDatabaseSeeder;
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
 *
 * Bao gồm demo data:
 *   - MarketplaceDemoSeeder: 3 free users (trust_level 1-2) + 5 open campaigns
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
            JobTitleDatabaseSeeder::class,
            LeadPipelineStageSeeder::class,   // global template stages (org_id = null)
            LeadSourceSeeder::class,           // global template sources (org_id = null)

            // ── 10. Module Lead: stages + sources cho demo org ────────────
            LeadDatabaseSeeder::class,

            // ── 11. Tuyển dụng: pipeline stages mặc định ─────────────────
            RecruitmentDatabaseSeeder::class,

            // ── 12. Đăng tuyển: skill masters + benefit masters ───────────
            JobPostingDatabaseSeeder::class,

            // ── 13. Assessment: TDWCF, 5-Pillar, certifications, sandbox ──
            AssessmentDatabaseSeeder::class,

            // ── 14. Survey: permissions, AI Readiness, scoring config ──────
            SurveyDatabaseSeeder::class,

            // ── 15. AI Copilot: system agents + system prompts ────────────
            AiCopilotDatabaseSeeder::class,

            // ── 16. Marketplace demo: 3 free users + 5 open campaigns ─────
            MarketplaceDemoSeeder::class,

            // ── 17. Deployment: survey templates + 1 bản mẫu vertical mặc định ──
            DataCollectionV1Seeder::class,
            ReadinessV1SurveySeeder::class,
            DefaultVerticalTemplateSeeder::class,

            // ── 18. OCOP Rubric: permissions + 5 hạng sao + 26 Bộ sản phẩm ──
            OcopRubricDatabaseSeeder::class,

            // ── 19. Business Solution: permissions + verticals + 3 solution bespoke ──
            BusinessSolutionDatabaseSeeder::class,

            // ── 20. Business Blueprint: permissions (blueprint.*) ─────────
            BusinessBlueprintDatabaseSeeder::class,

            // ── 21. Organization Solution: permissions (organization_solution.*) ──
            OrganizationSolutionDatabaseSeeder::class,

            // ── 22. Deployment Engine: permissions (deployment_engine.*) ──
            DeploymentEnginePermissionSeeder::class,

            // ── 23. Demo end-to-end: HTX Tiên Dương kích hoạt + deploy AI-TXNG ──
            HtxTienDuongDemoSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('  ✓ Tất cả dữ liệu mặc định đã được seed thành công.');
        $this->command->newLine();
    }
}
