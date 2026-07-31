<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\ActivityLog\Database\Seeders\ActivityLogPermissionsSeeder;
use Modules\Approval\Database\Seeders\ApprovalDatabaseSeeder;
use Modules\Assessment\Database\Seeders\AssessmentDatabaseSeeder;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Lead\Database\Seeders\LeadDatabaseSeeder;
use Modules\LeadPipelineStage\Database\Seeders\LeadPipelineStageSeeder;
use Modules\LeadSource\Database\Seeders\LeadSourceSeeder;
use Modules\Aicem\Database\Seeders\AicemDatabaseSeeder;
use Modules\Banner\Database\Seeders\BannerDatabaseSeeder;
use Modules\CoreIdeaExtractor\Database\Seeders\CoreIdeaExtractorDatabaseSeeder;
use Modules\Event\Database\Seeders\EventDatabaseSeeder;
use Modules\Event\Database\Seeders\EventDemoSeeder;
use Modules\Menu\Database\Seeders\MenuDatabaseSeeder;
use Modules\Page\Database\Seeders\PageDatabaseSeeder;
use Modules\Product\Database\Seeders\ProductDatabaseSeeder;
use Modules\Post\Database\Seeders\PostDatabaseSeeder;
use Modules\Post\Database\Seeders\PostDemoSeeder;
use Modules\Post\Database\Seeders\ProvinceShowcaseCategorySeeder;
use Modules\Ocop\Database\Seeders\OcopDatabaseSeeder;
use Modules\ProvinceShowcase\Database\Seeders\ProvinceShowcaseDemoSeeder;
use Modules\ProvinceShowcase\Database\Seeders\ProvinceSlugBackfillSeeder;
use Modules\Organization\Database\Seeders\OrganizationRolePermissionSeeder;
use Modules\Subscription\Database\Seeders\SubscriptionDatabaseSeeder;
use Modules\Survey\Database\Seeders\SurveyDatabaseSeeder;
use Modules\Video\Database\Seeders\VideoDatabaseSeeder;

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

        // ── 0. Dữ liệu hành chính (regions/provinces/wards) — command riêng (không phải
        // Seeder class) vì đọc trực tiếp datafiles/provinces.json, đã tồn tại từ trước cho
        // Customer/Lead/Event. PHẢI chạy TRƯỚC mọi seeder demo dùng province_code (Event, Post,
        // Ocop, ProvinceShowcase) — trên fresh DB (migrate:fresh xoá sạch bảng), không chạy lại
        // lệnh này thì provinces/wards rỗng, mọi denormalize province_name/ward_name sẽ null im
        // lặng (không lỗi) — xem spec/Province_Showcase_Technical_Specification.md §8 Phase 1.
        // Idempotent (Model::upsert theo khoá tự nhiên), an toàn chạy lại trên DB đã có dữ liệu.
        Artisan::call('import:provinces-wards');
        $this->command->info('  ✓ ' . trim(Artisan::output()));

        $this->call([
            // ── 0b. spec/Province_Showcase_Technical_Specification.md §3.1 — backfill
            // provinces.slug ngay sau khi import xong (route công khai /{type}/{slug}) ──
            ProvinceSlugBackfillSeeder::class,

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

            // ── 27c. Page: permission page.manage (cùng nguyên tắc Banner, PHẢI đứng sau bước 27)
            // + 4 trang tĩnh mặc định (draft, is_system=true) — spec/Page_Static_Pages_Technical_
            // Specification.md §6/§7 ──
            PageDatabaseSeeder::class,

            // ── 27d. CoreIdeaExtractor: permission core_idea_extractor.use — gán cho
            // platform_content_editor/platform_content_head (role do ApprovalDatabaseSeeder tạo
            // ở bước 27), cùng nguyên tắc Banner/Page, PHẢI đứng sau bước đó ──
            CoreIdeaExtractorDatabaseSeeder::class,

            // ── 27e. Video: permission video.manage — gán cho platform_ops/platform_content_head
            // (role do ApprovalDatabaseSeeder tạo ở bước 27), cùng nguyên tắc Banner/Page,
            // PHẢI đứng sau bước đó (spec/Video_Management_Technical_Specification.md §6.7) ──
            VideoDatabaseSeeder::class,

            // ── 28. Demo content: bài viết + sự kiện mẫu đã xuất bản (đọc cho trang public) ──
            PostDemoSeeder::class,
            EventDemoSeeder::class,

            // ── 28b. Province Showcase: 2 category con "du-lich-gia-dinh" (PHẢI đứng sau
            // PostDemoSeeder — cần category cha tồn tại), rồi permission ocop.manage, rồi demo
            // nội dung Huế/Cà Mau (bài viết/OCOP/sự kiện/banner) — spec/Province_Showcase_
            // Technical_Specification.md §8 ──
            ProvinceShowcaseCategorySeeder::class,
            OcopDatabaseSeeder::class,
            ProvinceShowcaseDemoSeeder::class,

            // ── 29. Menu (header): mega-menu công khai — cần category_id thật (bước 28) ──
            MenuDatabaseSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('  ✓ Tất cả dữ liệu mặc định đã được seed thành công.');
        $this->command->newLine();
    }
}
