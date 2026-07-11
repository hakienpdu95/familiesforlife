<?php

namespace Modules\Aicem\Database\Seeders;

use App\Models\User;
use App\Services\AI\CostCalculator;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Modules\Aicem\Enums\GenerationRunStatus;
use Modules\Aicem\Enums\SuggestionStatus;
use Modules\Aicem\Features\ExampleLearning\Actions\ApproveExampleCandidateAction;
use Modules\Aicem\Features\ExampleLearning\Actions\RejectExampleCandidateAction;
use Modules\Aicem\Features\KnowledgeBase\Actions\CreateKnowledgeDocumentAction;
use Modules\Aicem\Features\KnowledgeBase\Data\KnowledgeDocumentData;
use Modules\Aicem\Models\AicemExampleCandidate;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemKnowledgeDocument;
use Modules\Aicem\Models\AicemMonthlyBudgetUsage;
use Modules\Aicem\Models\AicemWorkflow;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Features\ArticleAuthoring\Actions\ApproveArticleTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\SubmitArticleForReviewAction;
use Modules\Post\Features\ArticleAuthoring\Actions\UpdateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Features\CatalogManagement\Actions\CreateProductAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;

/**
 * Seed dữ liệu DEMO đầy đủ cho AICEM — dùng để quan sát/hiểu toàn bộ luồng module (KHÔNG chạy
 * trong CI/production, chỉ dành cho môi trường dev). Dựng lại ĐÚNG ví dụ ở
 * spec/AICEM_Technical_Specification.md mục 6.8 (2 bài viết cùng subject_type=post_article nhưng
 * nhận knowledge base khác nhau theo scope) để thấy trực quan cơ chế resolve theo taxonomy —
 * mở workflow "headline"/"seo_audit" trên 2 bài viết demo sẽ tự thấy prompt khác nhau.
 *
 * Đi qua ĐÚNG Action thật (CreateArticleAction, CreateTranslationAction, UpdateTranslationAction,
 * Submit/Approve/PublishArticleAction, CreateKnowledgeDocumentAction, Approve/RejectExampleCandidateAction...)
 * thay vì insert thẳng DB, để dữ liệu demo phản ánh đúng luồng thật (VD publish bài is_featured=true
 * tự bắn event tạo example_candidate qua listener thật của Phase 5, không phải giả lập).
 *
 * Subject của AICEM cho post_article là PostArticleTranslation (Publishing Engine Phase 13 —
 * title/excerpt/seo_title/seo_description/blocks giờ per-locale, không còn trên PostArticle) — mọi $subject_id dưới
 * đây là translation_id, khớp config('aicem_subjects.post_article.model'). Publish giờ đi qua
 * state machine đủ bước Draft→Submitted→Approved→Published (PublishArticleAction không còn nhận
 * Draft trực tiếp như trước Publishing Engine) — xem createArticleWithTranslation()/
 * createAndPublishArticle().
 *
 * Idempotent theo tiêu đề/tên — chạy lại không tạo trùng.
 *
 * Chạy: php artisan db:seed --class="Modules\Aicem\Database\Seeders\AicemDemoDataSeeder"
 */
class AicemDemoDataSeeder extends Seeder
{
    private Organization $organization;
    private int $userId;

    public function run(): void
    {
        $this->organization = Organization::query()->where('is_system', false)->orderBy('id')->firstOrFail();

        $this->seedDemoUserRoles();

        // Marketing là role thực sự chạy workflow AI (aicem.use, mục 12) — dùng làm "tác giả" cho
        // dữ liệu demo (created_by) để phản ánh đúng luồng thật, không phải vì lý do kỹ thuật.
        $demoUser = User::where('organization_id', $this->organization->id)
            ->where('email', 'marketing@demo.test')
            ->first()
            ?? User::where('organization_id', $this->organization->id)
                ->where('email', 'like', '%@demo.test')
                ->orderBy('id')
                ->first()
            ?? User::where('organization_id', $this->organization->id)->firstOrFail();

        $this->userId = $demoUser->id;
        Auth::login($demoUser);

        // Đảm bảo có sẵn workflow headline/seo_audit/full_optimization (Phase 3/4) — idempotent,
        // an toàn gọi lại.
        $this->call(AicemDefaultWorkflowSeeder::class);

        TenantContext::runForOrganization($this->organization, function () {
            $this->command?->info("Seeding Aicem demo data cho Organization #{$this->organization->id} ({$this->organization->name})...");

            $this->seedKnowledgeBase();
            [$categorySleep, $categoryFamily] = $this->seedPostCategories();
            [$translationSleep, $translationFamily] = $this->seedScopeExampleArticles($categorySleep, $categoryFamily);
            $this->seedDemoProducts();
            $this->seedGenerationRunsAndSuggestions($translationSleep, $translationFamily);
            $this->seedBudget();
            $this->seedExampleCandidateStates();

            $this->command?->info('  ✓ Aicem demo data seeded.');

            $this->printUsageGuide();
        });

        Auth::logout();
    }

    /**
     * In hướng dẫn thao tác ra console sau khi seed — map dữ liệu demo → luồng module → chính xác
     * việc cần làm (đăng nhập tài khoản nào, mở đường dẫn nào, quan sát gì). Đọc lại dữ liệu vừa
     * seed để in đúng URL thật (article dùng uuid làm route key). Toàn bộ nội dung này cũng có
     * trong Modules/Aicem/docs/DEMO-WALKTHROUGH.md (bản đọc offline, chi tiết hơn).
     */
    private function printUsageGuide(): void
    {
        $cmd = $this->command;
        if (! $cmd) {
            return;
        }

        $base = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
        $url  = fn (string $name, mixed $param = null) => $base . parse_url(route($name, $param, false), PHP_URL_PATH);

        $translationSleep  = PostArticleTranslation::with('article')->where('title', 'Cách chọn nệm an toàn cho trẻ sơ sinh')->first();
        $translationFamily = PostArticleTranslation::with('article')->where('title', '5 hoạt động vui chơi cuối tuần cho cả nhà')->first();
        $translationFailed = PostArticleTranslation::with('article')->where('title', 'Có nên cho trẻ dùng thiết bị điện tử trước 2 tuổi?')->first();
        $productPrem       = Product::where('name', 'Nệm chống trào ngược cao cấp Babycare Premium')->first();

        $line = fn (string $s = '') => $cmd->line($s);
        $hr   = fn () => $cmd->line('  ' . str_repeat('─', 74));

        $line();
        $cmd->line('  <fg=black;bg=cyan> AICEM DEMO — HƯỚNG DẪN THAO TÁC </>');
        $line();
        $line("  Base URL      : <fg=cyan>{$base}</>");
        $line('  Mật khẩu login: <fg=cyan>password</> (cho mọi tài khoản *@demo.test)');
        $line();
        $line('  <fg=yellow>⚠ Nút "Chạy AI" chỉ hoạt động khi có queue worker chạy nền:</>');
        $line('      <fg=green>php artisan queue:listen --tries=1</>');
        $line('    Chưa cấu hình API key → run sẽ FAIL rõ ràng (không treo). Nhập key thật ở');
        $line('    trang "Cấu hình AICEM" hoặc set AI_DEFAULT_API_KEY trong .env để chạy thật.');
        $line();

        $hr();
        $line('  <options=bold>TÀI KHOẢN THEO VAI TRÒ</> (mục 12 — quyền quyết định thấy được gì)');
        $hr();
        $line('  <fg=cyan>marketing@demo.test</>  Marketing    → chạy workflow AI, accept/reject đề xuất');
        $line('  <fg=cyan>ai_op@demo.test</>      AI Operator  → sửa Knowledge Base, duyệt ví dụ mẫu');
        $line('  <fg=cyan>admin@demo.test</>      System Admin → cấu hình provider / API key / hạn mức');
        $line('  <fg=cyan>ceo@demo.test</>        CEO          → xem Dashboard tổng quan (read-only)');
        $line();

        $hr();
        $line('  <options=bold>KỊCH BẢN 1 — Cơ chế scope (mục 6.8)</> · login <fg=cyan>marketing@demo.test</>');
        $hr();
        $line('  2 bài viết cùng loại (post_article) nhưng nhận Knowledge Base KHÁC nhau theo');
        $line('  category/format — mở panel AI dưới trang sửa bài, xem dòng "bối cảnh" (taxonomy):');
        if ($translationSleep) {
            $line('   • Bài "nệm an toàn" (category=an-toan-giac-ngu):');
            $line('     <fg=cyan>' . $url('backend.post.articles.edit', $translationSleep->article) . '</>');
            $line('     → nhận thêm doc "E-E-A-T an toàn giấc ngủ" (cảnh báo SIDS/dẫn nguồn AAP)');
        }
        if ($translationFamily) {
            $line('   • Bài "5 hoạt động" (format=tip):');
            $line('     <fg=cyan>' . $url('backend.post.articles.edit', $translationFamily->article) . '</>');
            $line('     → nhận thêm doc "Văn phong Mẹo hay (tip)" thay vì doc an toàn giấc ngủ');
        }
        $line();

        $hr();
        $line('  <options=bold>KỊCH BẢN 2 — Accept/Reject đề xuất (Phase 3/9.1)</> · login <fg=cyan>marketing@demo.test</>');
        $hr();
        $line('  Dữ liệu suggestion đã seed sẵn đủ trạng thái để bấm thử ngay (không cần chạy AI):');
        if ($translationSleep) {
            $line('   • Bài "nệm an toàn" → 1 đề xuất <fg=yellow>PENDING</> (tối ưu tiêu đề): bấm Chấp nhận →');
            $line('     tiêu đề bài đổi thật; hoặc Từ chối. (đã có sẵn 1 đề xuất seo_description ACCEPTED)');
        }
        if ($translationFamily) {
            $line('   • Bài "5 hoạt động" → 1 đề xuất <fg=red>REJECTED</> + 1 đề xuất <fg=yellow>STALE</> (badge "Đã');
            $line('     thay đổi — chạy lại AI": mô phỏng nội dung bị sửa tay sau khi AI phân tích — mục 9.1)');
        }
        if ($translationFailed) {
            $line('   • Bài "thiết bị điện tử" → run <fg=red>FAILED</> (panel hiện lỗi API key rõ ràng):');
            $line('     <fg=cyan>' . $url('backend.post.articles.edit', $translationFailed->article) . '</>');
        }
        $line();

        $hr();
        $line('  <options=bold>KỊCH BẢN 3 — Knowledge Base + version/rollback (Phase 2)</> · login <fg=cyan>ai_op@demo.test</>');
        $hr();
        $line('  <fg=cyan>' . $url('backend.aicem.knowledge-documents.index') . '</>');
        $line('  14 tài liệu đủ 3 tầng: DNA chung (skill/brand/persona) · chuyên môn theo');
        $line('  subject_type (eeat_checklist/seo_rules...) · theo scope (an-toan-giac-ngu, tip).');
        $line('  → Sửa 1 tài liệu rồi Lưu → mở lại thấy "Lịch sử phiên bản" → bấm Khôi phục.');
        $line();

        $hr();
        $line('  <options=bold>KỊCH BẢN 4 — Duyệt ví dụ mẫu tự động (Phase 5)</> · login <fg=cyan>ai_op@demo.test</>');
        $hr();
        $line('  <fg=cyan>' . $url('backend.aicem.example-candidates.index') . '</>');
        $line('  Publish bài is_featured=true tự sinh candidate example_good (qua listener thật).');
        $line('  3 candidate seed sẵn: 1 <fg=yellow>pending</> (bấm Duyệt → tạo Knowledge Doc thật),');
        $line('  1 <fg=green>approved</>, 1 <fg=red>rejected</> (đổi bộ lọc trạng thái để xem cả 3).');
        $line();

        $hr();
        $line('  <options=bold>KỊCH BẢN 5 — Dashboard + Cấu hình (Phase 4/6)</>');
        $hr();
        $line('  CEO xem tổng quan (chi phí tháng, prompt-cache tokens, top workflow, 20 run gần nhất):');
        $line('   login <fg=cyan>ceo@demo.test</> → <fg=cyan>' . $url('backend.aicem.dashboard') . '</>');
        $line('  Admin cấu hình provider/API key (BYOK) + hạn mức $20/tháng + rate limit:');
        $line('   login <fg=cyan>admin@demo.test</> → <fg=cyan>' . $url('backend.aicem.settings') . '</>');
        $line();

        if ($productPrem) {
            $hr();
            $line('  <options=bold>KỊCH BẢN 6 — Panel AI trên Sản phẩm (module chỉ định thứ 2)</> · <fg=cyan>marketing@demo.test</>');
            $hr();
            $line('  Panel AI dùng chung cũng nhúng vào trang sửa sản phẩm (taxonomy = price_tier):');
            $line('   • SP cao cấp (premium) → nhận doc "Copy chuyển đổi cao cấp":');
            $line('     <fg=cyan>' . $url('backend.products.edit', $productPrem) . '</>');
            $line();
        }

        $hr();
        $line('  Chi tiết đầy đủ: <fg=cyan>Modules/Aicem/docs/DEMO-WALKTHROUGH.md</>');
        $line('  Chạy lại seeder an toàn (idempotent): không tạo trùng dữ liệu.');
        $line();
    }

    /**
     * Các user demo (*@demo.test) trong DB dev hiện KHÔNG có role Spatie nào được gán (khoảng
     * trống có sẵn từ trước, không liên quan AICEM) — nếu để nguyên, đăng nhập bằng bất kỳ user
     * demo nào cũng không thấy được panel AI/dashboard/cấu hình vì mọi nơi đều gate theo permission
     * (mục 12). Gán đúng role khớp tên email để có thể đăng nhập quan sát toàn bộ luồng theo từng
     * góc nhìn role (Marketing chạy AI, AI Operator sửa knowledge base, System Admin cấu hình...).
     * CHỈ gán khi user đó chưa có role nào — không đụng vào nếu đã tự cấu hình.
     */
    private function seedDemoUserRoles(): void
    {
        $emailToRole = [
            'ceo@demo.test'       => 'ceo',
            'sales@demo.test'     => 'sales',
            'ops@demo.test'       => 'ops',
            'marketing@demo.test' => 'marketing',
            'hr@demo.test'        => 'hr',
            'ai_op@demo.test'     => 'ai_operator',
            'admin@demo.test'     => 'system_admin',
            'viewer@demo.test'    => 'viewer',
        ];

        foreach ($emailToRole as $email => $role) {
            $user = User::where('organization_id', $this->organization->id)->where('email', $email)->first();

            if ($user && $user->roles()->count() === 0) {
                $user->assignRole($role);
            }
        }
    }

    /**
     * Knowledge base minh hoạ đủ 3 tầng context (mục 5.1) + example_good/bad + custom_note —
     * bao gồm đúng bộ 3 document dùng trong ví dụ mục 6.8 (2 loại eeat_checklist/category_style_guide
     * với scope khác nhau khớp đúng 2 bài viết demo bên dưới).
     */
    private function seedKnowledgeBase(): void
    {
        $docs = [
            // ── Tầng 1 — DNA chung toàn tổ chức (scope=null) ──────────────────────────
            [
                'type' => 'skill', 'subject_type' => null,
                'title' => 'Giọng văn biên tập',
                'content' => "Giọng văn thân thiện, đáng tin cậy như một người bạn từng trải trong nuôi dạy con. "
                    . 'Tránh thuật ngữ y khoa khó hiểu, luôn giải thích lại bằng ngôn ngữ đơn giản. '
                    . 'Ưu tiên câu ngắn, đoạn văn không quá 4 dòng.',
            ],
            [
                'type' => 'brand_guideline', 'subject_type' => null,
                'title' => 'Quy chuẩn thương hiệu',
                'content' => 'Tên thương hiệu: FamiliesForLife. Không dùng ngôn ngữ gây lo lắng thái quá (fear-mongering). '
                    . 'Luôn kết thúc bài bằng 1 lời khuyên hành động cụ thể (actionable takeaway).',
            ],
            [
                'type' => 'audience_personas', 'subject_type' => null,
                'title' => 'Đối tượng đọc chính',
                'content' => 'Phụ huynh có con từ 0-6 tuổi, phần lớn là mẹ (25-40 tuổi), lo lắng về an toàn và phát triển '
                    . 'của con, thường đọc trên điện thoại vào buổi tối.',
            ],

            // ── Tầng 2 — chuyên môn post_article, scope=null (baseline áp dụng mọi bài) ──
            [
                'type' => 'eeat_checklist', 'subject_type' => 'post_article',
                'title' => 'Checklist E-E-A-T chung', 'priority' => 100,
                'content' => 'Checklist bắt buộc: (1) Có ít nhất 1 nguồn tham khảo uy tín (bệnh viện, viện nghiên cứu). '
                    . '(2) Tác giả/người duyệt có chuyên môn liên quan. (3) Ngày cập nhật rõ ràng. '
                    . '(4) Không đưa lời khuyên y tế tuyệt đối, luôn khuyến nghị tham khảo bác sĩ khi cần.',
            ],
            [
                'type' => 'seo_keyword_rules', 'subject_type' => 'post_article',
                'title' => 'Quy tắc SEO chung', 'priority' => 100,
                'content' => 'seo_title tối đa 60 ký tự, chứa từ khoá chính ở đầu câu. seo_description tối đa 160 ký tự, '
                    . 'có call-to-action nhẹ nhàng. Tránh nhồi từ khoá quá 2 lần.',
            ],

            // ── Tầng 3 — theo scope cụ thể, ĐÚNG ví dụ mục 6.8 ───────────────────────────
            [
                'type' => 'eeat_checklist', 'subject_type' => 'post_article',
                'title' => 'E-E-A-T bổ sung: chủ đề an toàn giấc ngủ', 'priority' => 200,
                'scope' => ['category_slugs' => ['an-toan-giac-ngu']], 'scope_match' => 'any',
                'content' => 'Mọi khuyến nghị về nệm/chăn/gối cho trẻ sơ sinh PHẢI dẫn nguồn khuyến cáo của Viện Hàn lâm '
                    . 'Nhi khoa Hoa Kỳ (AAP) hoặc Viện Nhi khoa Việt Nam, và PHẢI cảnh báo rõ nguy cơ đột tử ở trẻ sơ sinh '
                    . '(SIDS) nếu nội dung liên quan tư thế ngủ/đồ dùng trong cũi.',
            ],
            [
                'type' => 'category_style_guide', 'subject_type' => 'post_article',
                'title' => 'Văn phong định dạng Mẹo hay (tip)', 'priority' => 100,
                'scope' => ['format' => ['tip']], 'scope_match' => 'any',
                'content' => "Định dạng 'Mẹo hay': liệt kê dạng số thứ tự, mỗi mục là 1 câu ngắn hành động được ngay, "
                    . 'tối đa 5 mục, không cần dẫn nguồn học thuật.',
            ],

            // ── Ví dụ tốt/xấu ─────────────────────────────────────────────────────────
            [
                'type' => 'example_good', 'subject_type' => 'post_article',
                'title' => 'Ví dụ bài viết tốt: mở bài trực tiếp vào vấn đề',
                'content' => "# 5 dấu hiệu bé mọc răng mẹ cần biết\n\nBé nhà bạn đột nhiên quấy khóc, chảy nhiều nước dãi? "
                    . 'Rất có thể bé đang mọc chiếc răng đầu tiên. Dưới đây là 5 dấu hiệu giúp mẹ nhận biết sớm...',
            ],
            [
                'type' => 'example_bad', 'subject_type' => 'post_article',
                'title' => 'Ví dụ bài viết cần tránh: mở bài lan man',
                'content' => "# Về vấn đề răng miệng của trẻ nhỏ\n\nNhư chúng ta đã biết, trẻ em là tương lai của đất nước, "
                    . 'và việc chăm sóc sức khỏe răng miệng cho trẻ từ xưa đến nay luôn là chủ đề được nhiều bậc phụ huynh '
                    . 'quan tâm sâu sắc qua nhiều thế hệ...',
            ],

            // ── Escape hatch ──────────────────────────────────────────────────────────
            [
                'type' => 'custom_note', 'subject_type' => 'post_article',
                'title' => 'Lưu ý tạm: chiến dịch Tết 2026', 'priority' => 900,
                'content' => "Trong tháng Tết, ưu tiên gợi ý tông vui tươi, có thể nhắc 'Tết' trong tiêu đề nếu phù hợp chủ đề bài.",
            ],

            // ── Product ───────────────────────────────────────────────────────────────
            [
                'type' => 'ads_compliance_rules', 'subject_type' => 'product',
                'title' => 'Quy định quảng cáo sản phẩm',
                'content' => "Không dùng từ 'tốt nhất', 'số 1', 'chữa khỏi' — vi phạm luật quảng cáo. Với sản phẩm liên quan "
                    . 'sức khoẻ trẻ em, không cam kết hiệu quả tuyệt đối.',
            ],
            [
                'type' => 'conversion_copy_rules', 'subject_type' => 'product',
                'title' => 'Copy chuyển đổi cho sản phẩm cao cấp', 'priority' => 150,
                'scope' => ['price_tier' => ['premium']], 'scope_match' => 'any',
                'content' => "Nhấn mạnh chất lượng, độ bền, chứng nhận an toàn (CE, ASTM...). Tránh ngôn ngữ 'giá rẻ', "
                    . "'tiết kiệm' — không phù hợp phân khúc.",
            ],
            [
                'type' => 'pricing_display_rules', 'subject_type' => 'product',
                'title' => 'Hiển thị giá cho sản phẩm giá tốt', 'priority' => 150,
                'scope' => ['price_tier' => ['budget']], 'scope_match' => 'any',
                'content' => "Nhấn mạnh tiết kiệm, giá tốt, phù hợp túi tiền. Có thể dùng cụm 'giá tốt', 'tiết kiệm chi phí'.",
            ],
        ];

        foreach ($docs as $doc) {
            if (AicemKnowledgeDocument::where('title', $doc['title'])->exists()) {
                continue;
            }

            app(CreateKnowledgeDocumentAction::class)->handle(new KnowledgeDocumentData(
                type: $doc['type'],
                title: $doc['title'],
                content: $doc['content'],
                subject_type: $doc['subject_type'] ?? null,
                scope: $doc['scope'] ?? null,
                scope_match: $doc['scope_match'] ?? 'any',
                priority: $doc['priority'] ?? null,
            ));
        }
    }

    /** @return array{0: PostCategory, 1: PostCategory} */
    private function seedPostCategories(): array
    {
        $sleep = PostCategory::firstOrCreate(
            ['slug' => 'an-toan-giac-ngu'],
            ['name' => 'An toàn giấc ngủ', 'is_active' => true, 'sort_order' => 1, 'created_by' => $this->userId]
        );

        $family = PostCategory::firstOrCreate(
            ['slug' => 'hoat-dong-gia-dinh'],
            ['name' => 'Hoạt động gia đình', 'is_active' => true, 'sort_order' => 2, 'created_by' => $this->userId]
        );

        return [$sleep, $family];
    }

    /**
     * 2 bài viết ĐÚNG ví dụ mục 6.8 — cùng subject_type=post_article nhưng khác category/format
     * nên nhận knowledge base khác nhau khi chạy workflow (tự kiểm chứng bằng cách mở panel AI
     * trên từng bài và so sánh).
     *
     * @return array{0: PostArticleTranslation, 1: PostArticleTranslation}
     */
    private function seedScopeExampleArticles(PostCategory $sleep, PostCategory $family): array
    {
        $translationSleep = PostArticleTranslation::where('title', 'Cách chọn nệm an toàn cho trẻ sơ sinh')->first()
            ?? $this->createAndPublishArticle(
                title: 'Cách chọn nệm an toàn cho trẻ sơ sinh',
                format: ArticleFormat::Article,
                excerpt: 'Hướng dẫn chọn nệm, chăn an toàn, giảm nguy cơ SIDS cho bé sơ sinh.',
                blocks: [
                    ['type' => 'text', 'html' => '<p>Giấc ngủ an toàn là ưu tiên hàng đầu trong những tháng đầu đời của bé.</p>'],
                    ['type' => 'text', 'html' => '<p>Mẹ nên chọn nệm có độ cứng vừa phải, không quá lún, và không đặt gối/chăn dày trong cũi.</p>'],
                ],
                isFeatured: true, // Phase 5 — publish sẽ tự tạo example_candidate qua listener thật
                categoryId: $sleep->id,
                seoTitle: 'Cách chọn nệm an toàn cho trẻ sơ sinh',
                seoDescription: 'Hướng dẫn chi tiết chọn nệm, chăn an toàn cho trẻ sơ sinh, giảm nguy cơ SIDS.',
            );

        $translationFamily = PostArticleTranslation::where('title', '5 hoạt động vui chơi cuối tuần cho cả nhà')->first()
            ?? $this->createAndPublishArticle(
                title: '5 hoạt động vui chơi cuối tuần cho cả nhà',
                format: ArticleFormat::Tip,
                excerpt: 'Gợi ý hoạt động cuối tuần gắn kết cả gia đình.',
                blocks: [
                    ['type' => 'text', 'html' => '<p>1. Cùng nấu ăn: giao cho bé 1 nhiệm vụ đơn giản như rửa rau.</p>'],
                    ['type' => 'text', 'html' => '<p>2. Đi công viên gần nhà vào sáng sớm khi trời mát.</p>'],
                ],
                categoryId: $family->id,
            );

        return [$translationSleep, $translationFamily];
    }

    private function seedDemoProducts(): void
    {
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'do-dung-so-sinh'],
            ['name' => 'Đồ dùng sơ sinh', 'is_active' => true, 'created_by' => $this->userId]
        );

        if (! Product::where('name', 'Nệm chống trào ngược cao cấp Babycare Premium')->exists()) {
            app(CreateProductAction::class)->handle(new ProductData(
                name: 'Nệm chống trào ngược cao cấp Babycare Premium',
                type: ProductType::Physical,
                status: ProductStatus::Active,
                category_id: $category->id,
                short_description: 'Nệm cao cấp chống trào ngược, đạt chứng nhận an toàn quốc tế.',
                description: 'Sản phẩm nhập khẩu, đạt chứng nhận CE, phù hợp trẻ sơ sinh.',
                price: 2_500_000,
                shopee_url: 'https://shopee.vn/demo-nem-premium',
            ));
        }

        if (! Product::where('name', 'Bình sữa cổ rộng giá tốt Mombella')->exists()) {
            app(CreateProductAction::class)->handle(new ProductData(
                name: 'Bình sữa cổ rộng giá tốt Mombella',
                type: ProductType::Physical,
                status: ProductStatus::Active,
                category_id: $category->id,
                short_description: 'Bình sữa cổ rộng, giá tốt, phù hợp gia đình tiết kiệm.',
                description: 'Chất liệu nhựa PP an toàn, dễ vệ sinh.',
                price: 89_000,
                shopee_url: 'https://shopee.vn/demo-binh-sua-budget',
            ));
        }
    }

    /**
     * Tạo sẵn generation run + suggestion ở đủ trạng thái để quan sát (KHÔNG gọi AI thật — chi
     * phí/token là số minh hoạ tính qua CostCalculator thật để nhất quán với công thức tính tiền).
     */
    private function seedGenerationRunsAndSuggestions(PostArticleTranslation $translationSleep, PostArticleTranslation $translationFamily): void
    {
        if (AicemGenerationRun::where('subject_id', $translationSleep->id)->exists()) {
            return; // đã seed trước đó
        }

        $headline = AicemWorkflow::where('slug', 'headline')->first();
        $seoAudit = AicemWorkflow::where('slug', 'seo_audit')->first();
        $fullOpt  = AicemWorkflow::where('slug', 'full_optimization')->first();

        if (! $headline || ! $seoAudit || ! $fullOpt) {
            $this->command?->warn('  ⚠ Thiếu workflow mặc định — bỏ qua seed generation runs.');

            return;
        }

        // Run 1 — seo_audit, cache MISS, ĐÃ QUYẾT ĐỊNH (accepted) — tạo TRƯỚC để created_at cũ
        // hơn run 2 bên dưới (Panel chỉ hiển thị suggestion đang chờ của run MỚI NHẤT theo
        // subject — created_at, không phải started_at giả lập — nên thứ tự tạo ở đây quyết định
        // cái nào Panel coi là "gần nhất" khi mở trang sửa bài).
        $run1 = AicemGenerationRun::create([
            'subject_type' => 'post_article', 'subject_id' => $translationSleep->id,
            'workflow_id' => $seoAudit->id, 'requested_by' => $this->userId,
            'provider' => 'anthropic', 'model' => 'claude-sonnet-5',
            'status' => GenerationRunStatus::Succeeded,
            'input_tokens' => 300, 'output_tokens' => 90,
            'cache_creation_tokens' => 600, 'cache_read_tokens' => 0,
            'cost_usd' => CostCalculator::calculate('anthropic', 'claude-sonnet-5', 300, 90, 600, 0),
            'started_at' => now()->subDays(2), 'completed_at' => now()->subDays(2)->addSeconds(6),
        ]);
        $run1->suggestions()->create([
            'organization_id' => $this->organization->id,
            'field' => 'seo_description', 'original_text' => $translationSleep->seo_description ?? '',
            'suggested_text' => 'Hướng dẫn chọn nệm, chăn an toàn cho trẻ sơ sinh theo khuyến cáo AAP, giảm nguy cơ SIDS.',
            'reason' => 'Bổ sung "theo khuyến cáo AAP" tăng độ tin cậy (E-E-A-T).',
            'status' => SuggestionStatus::Accepted,
            'decided_by' => $this->userId, 'decided_at' => now()->subDays(2)->addMinutes(10),
        ]);

        // Run 2 — headline, cache HIT (khối DNA/knowledge base đã cache từ run 1 — thấy rõ
        // cache_read_tokens > 0 và cost_usd thấp hơn hẳn run 1). Tạo SAU nên là run MỚI NHẤT của
        // bài viết này — mở trang sửa bài sẽ thấy ngay suggestion PENDING này để bấm thử Chấp
        // nhận/Từ chối.
        $run2 = AicemGenerationRun::create([
            'subject_type' => 'post_article', 'subject_id' => $translationSleep->id,
            'workflow_id' => $headline->id, 'requested_by' => $this->userId,
            'provider' => 'anthropic', 'model' => 'claude-sonnet-5',
            'status' => GenerationRunStatus::Succeeded,
            'input_tokens' => 850, 'output_tokens' => 120,
            'cache_creation_tokens' => 0, 'cache_read_tokens' => 600,
            'cost_usd' => CostCalculator::calculate('anthropic', 'claude-sonnet-5', 850, 120, 0, 600),
            'started_at' => now()->subDay(), 'completed_at' => now()->subDay()->addSeconds(8),
        ]);
        $run2->suggestions()->create([
            'organization_id' => $this->organization->id,
            'field' => 'title', 'original_text' => $translationSleep->title,
            'suggested_text' => 'Chọn Nệm An Toàn Cho Trẻ Sơ Sinh: Hướng Dẫn Từ A-Z',
            'reason' => "Tiêu đề gốc chưa có yếu tố hướng dẫn cụ thể, thêm 'Từ A-Z' tăng CTR.",
            'status' => SuggestionStatus::Pending,
        ]);

        // Run 3 — full_optimization trên bài "hoạt động gia đình" — 1 suggestion bị từ chối,
        // 1 suggestion đã STALE (mô phỏng nội dung bị sửa tay sau khi AI phân tích — mục 9.1).
        $firstBlockId = $translationFamily->contentBlocks()->orderBy('sort_order')->value('id');

        $run3 = AicemGenerationRun::create([
            'subject_type' => 'post_article', 'subject_id' => $translationFamily->id,
            'workflow_id' => $fullOpt->id, 'requested_by' => $this->userId,
            'provider' => 'anthropic', 'model' => 'claude-sonnet-5',
            'status' => GenerationRunStatus::Succeeded,
            'input_tokens' => 950, 'output_tokens' => 200,
            'cache_creation_tokens' => 0, 'cache_read_tokens' => 600,
            'cost_usd' => CostCalculator::calculate('anthropic', 'claude-sonnet-5', 950, 200, 0, 600),
            'started_at' => now()->subHours(5), 'completed_at' => now()->subHours(5)->addSeconds(12),
        ]);
        $run3->suggestions()->create([
            'organization_id' => $this->organization->id,
            'block_id' => $firstBlockId,
            'original_text' => '1. Cùng nấu ăn: giao cho bé 1 nhiệm vụ đơn giản như rửa rau.',
            'suggested_text' => '1. Vào bếp cùng con: hãy để bé rửa rau, một việc nhỏ nhưng giúp con cảm thấy được tin tưởng.',
            'reason' => 'Văn phong gần gũi hơn, thêm yếu tố cảm xúc.',
            'status' => SuggestionStatus::Rejected,
            'decided_by' => $this->userId, 'decided_at' => now()->subHours(4),
        ]);
        $run3->suggestions()->create([
            'organization_id' => $this->organization->id,
            'field' => 'excerpt',
            'original_text' => 'Gợi ý hoạt động cuối tuần cho gia đình bận rộn', // KHÁC excerpt thật hiện tại
            'suggested_text' => 'Gợi ý hoạt động cuối tuần cho gia đình bận rộn, dễ thực hiện, không tốn kém.',
            'reason' => 'Thêm "dễ thực hiện, không tốn kém" đúng insight audience persona.',
            'status' => SuggestionStatus::Stale, // excerpt thật đã đổi sau khi run này "generate"
        ]);

        // Run 4 — thất bại, đặt trên 1 bài viết RIÊNG (không phải translationFamily) để không che
        // mất suggestion rejected/stale ở trên khi mở panel (Panel chỉ hiển thị run MỚI NHẤT/subject).
        // Không publish (chỉ cần tồn tại để gắn 1 generation run FAILED).
        $translationFailed = PostArticleTranslation::where('title', 'Có nên cho trẻ dùng thiết bị điện tử trước 2 tuổi?')->first()
            ?? $this->createArticleWithTranslation(
                title: 'Có nên cho trẻ dùng thiết bị điện tử trước 2 tuổi?',
                format: ArticleFormat::Article,
                excerpt: 'Góc nhìn khoa học về màn hình và trẻ nhỏ.',
                blocks: [['type' => 'text', 'html' => '<p>Học viện Nhi khoa Hoa Kỳ khuyến cáo hạn chế màn hình trước 18-24 tháng tuổi.</p>']],
            );

        AicemGenerationRun::create([
            'subject_type' => 'post_article', 'subject_id' => $translationFailed->id,
            'workflow_id' => $headline->id, 'requested_by' => $this->userId,
            'provider' => 'anthropic', 'model' => 'claude-sonnet-5',
            'status' => GenerationRunStatus::Failed,
            'error_message' => 'API key không hợp lệ hoặc đã hết hạn — vui lòng kiểm tra cấu hình provider trong "Cấu hình AICEM".',
            'started_at' => now()->subHours(3), 'completed_at' => now()->subHours(3)->addSeconds(2),
        ]);
    }

    private function seedBudget(): void
    {
        if ($this->organization->ai_monthly_budget_usd === null) {
            $this->organization->update(['ai_monthly_budget_usd' => 20.00]);
        }

        $yearMonth = now()->format('Y-m');
        $usage     = AicemMonthlyBudgetUsage::firstOrCreate(
            ['year_month' => $yearMonth],
            ['reserved_usd' => 0, 'settled_usd' => 0]
        );

        if ((float) $usage->settled_usd === 0.0 && (float) $usage->reserved_usd === 0.0) {
            $usage->update(['settled_usd' => 0.05]);
        }
    }

    /**
     * Đủ 3 trạng thái candidate (Phase 5): pending (bài "nệm an toàn" seed ở trên đã tự tạo qua
     * listener thật lúc publish, để nguyên cho người xem tự bấm Duyệt/Từ chối thử), approved,
     * rejected — 2 cái sau đi qua ĐÚNG Action approve/reject thật, không tự set cột.
     */
    private function seedExampleCandidateStates(): void
    {
        $this->seedCandidateArticle(
            title: 'Nhật ký làm mẹ: hành trình 100 ngày đầu bên con',
            excerpt: 'Câu chuyện thật, cảm động và nhiều bài học từ 100 ngày đầu làm mẹ.',
            decide: fn (AicemExampleCandidate $candidate) => app(ApproveExampleCandidateAction::class)->handle($candidate, $this->userId),
        );

        $this->seedCandidateArticle(
            title: 'Ăn dặm kiểu Nhật hay kiểu BLW: tranh cãi chưa hồi kết',
            excerpt: 'So sánh 2 phương pháp ăn dặm phổ biến đang được nhiều mẹ quan tâm.',
            decide: fn (AicemExampleCandidate $candidate) => app(RejectExampleCandidateAction::class)->handle($candidate, $this->userId),
        );
    }

    private function seedCandidateArticle(string $title, string $excerpt, \Closure $decide): void
    {
        if (PostArticleTranslation::where('title', $title)->exists()) {
            return;
        }

        $translation = $this->createAndPublishArticle(
            title: $title,
            format: ArticleFormat::Article,
            excerpt: $excerpt,
            blocks: [['type' => 'text', 'html' => '<p>' . e($excerpt) . '</p>']],
            isFeatured: true,
        );

        $candidate = AicemExampleCandidate::where('subject_id', $translation->id)->first();

        if ($candidate) {
            $decide($candidate);
        }
    }

    /**
     * Tạo 1 PostArticle "vỏ" + bản dịch vi với đủ title/excerpt/seo/blocks — KHÔNG publish
     * (dùng khi chỉ cần tồn tại bài viết để gắn dữ liệu demo khác, vd 1 generation run FAILED).
     */
    private function createArticleWithTranslation(
        string $title,
        ArticleFormat $format,
        ?string $excerpt = null,
        array $blocks = [],
        bool $isFeatured = false,
        ?int $categoryId = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
    ): PostArticleTranslation {
        $article = app(CreateArticleAction::class)->handle(new ArticleData(
            format: $format,
            is_featured: $isFeatured,
            category_ids: $categoryId ? [$categoryId] : [],
            is_primary_category_id: $categoryId,
        ));

        $translation = app(CreateTranslationAction::class)->handle($article, 'vi', new TranslationData(title: $title));

        return app(UpdateTranslationAction::class)->handle($translation, new TranslationData(
            title: $title,
            excerpt: $excerpt,
            seo_title: $seoTitle,
            seo_description: $seoDescription,
            blocks: $blocks,
        ));
    }

    /**
     * Như createArticleWithTranslation() nhưng publish luôn qua đúng state machine thật
     * (Draft→Submitted→Approved→Published — PublishArticleAction không còn nhận Draft trực
     * tiếp từ Publishing Engine Phase 13).
     */
    private function createAndPublishArticle(
        string $title,
        ArticleFormat $format,
        ?string $excerpt = null,
        array $blocks = [],
        bool $isFeatured = false,
        ?int $categoryId = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
    ): PostArticleTranslation {
        $translation = $this->createArticleWithTranslation(
            title: $title, format: $format, excerpt: $excerpt, blocks: $blocks,
            isFeatured: $isFeatured, categoryId: $categoryId, seoTitle: $seoTitle, seoDescription: $seoDescription,
        );

        $translation = app(SubmitArticleForReviewAction::class)->handle($translation);
        $translation = app(ApproveArticleTranslationAction::class)->handle($translation);

        return app(PublishArticleAction::class)->handle($translation);
    }
}
