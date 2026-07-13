<?php

namespace Modules\Post\Database\Seeders;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\ApproveArticleTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\SubmitArticleForReviewAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;

/**
 * Demo dữ liệu minh hoạ 2 tầng duyệt bài viết (platform_content_editor → platform_content_head — Platform
 * Approval Gateway, spec/Workflow_Approval_Technical_Specification.md §18.10). Chạy Action
 * THẬT (không insert thẳng DB) do đúng vai (marketing tạo/gửi, editor duyệt sơ bộ, platform_content_head
 * duyệt cuối) để log/timestamp sinh ra thực tế.
 *
 * KHÔNG nằm trong SystemDataSeeder (demo-only, giống ProductApprovalDemoSeeder) — chạy thủ công:
 *   php artisan db:seed --class="Modules\Post\Database\Seeders\PostReviewDemoSeeder"
 *
 * Idempotent — mỗi bài demo dùng slug cố định, bỏ qua nếu đã tồn tại.
 */
class PostReviewDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo')->first();
        if (! $org) {
            $this->command->warn('  ⚠ Không tìm thấy Organization slug=demo — chạy OrganizationSeeder trước.');

            return;
        }

        $marketing = User::where('email', 'marketing@demo.test')->first();
        $editor    = User::withoutGlobalScopes()->where('email', 'editor@system.local')->first();
        $head      = User::withoutGlobalScopes()->where('email', 'content-head@system.local')->first();

        if (! $marketing || ! $editor || ! $head) {
            $this->command->warn('  ⚠ Thiếu user demo (marketing@demo.test / editor@system.local / content-head@system.local) — chạy UserSeeder + ContentReviewHierarchySeeder trước.');

            return;
        }

        TenantContext::set($org);
        $previousUser = Auth::user();

        $this->seedDraft($marketing);
        $this->seedSubmitted($marketing, 'demo-review-cham-soc-khach-hang', 'Bí quyết chăm sóc khách hàng hiệu quả (Demo — chờ biên tập viên)');
        $this->seedSubmitted($marketing, 'demo-review-toi-uu-chi-phi', '5 mẹo tối ưu chi phí vận hành doanh nghiệp (Demo — chờ biên tập viên)');
        $this->seedApproved($marketing, $editor);
        $this->seedPublished($marketing, $editor, $head);

        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info('  ✓ Post review demo data seeded (5 bài — Draft/2×Submitted/Approved/Published).');
    }

    private function findOrCreateTranslation(string $slug, string $title, User $createdBy): array
    {
        $existing = PostArticleTranslation::where('slug', $slug)->first();
        if ($existing) {
            return [$existing, false];
        }

        Auth::login($createdBy);

        $article = app(CreateArticleAction::class)->handle(ArticleData::from([
            'format'          => 'article',
            'cover_image_url' => null,
            'is_featured'     => false,
            'category_ids'    => [],
            'is_sponsored'    => false,
        ]));

        $translation = app(CreateTranslationAction::class)->handle($article, 'vi', TranslationData::from([
            'title'           => $title,
            'slug'            => $slug,
            'excerpt'         => "Dữ liệu demo minh hoạ luồng duyệt bài viết 2 tầng — {$title}.",
            'blocks'          => [],
        ]));

        return [$translation, true];
    }

    private function seedDraft(User $marketing): void
    {
        // Không transition gì thêm — mặc định Draft ngay sau khi tạo, chưa gửi duyệt.
        $this->findOrCreateTranslation('demo-review-xu-huong-marketing-2026', 'Xu hướng marketing số 2026 (Demo — Draft, chưa gửi duyệt)', $marketing);
    }

    private function seedSubmitted(User $marketing, string $slug, string $title): void
    {
        [$translation, $isNew] = $this->findOrCreateTranslation($slug, $title, $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitArticleForReviewAction::class)->handle($translation);
    }

    private function seedApproved(User $marketing, User $editor): void
    {
        [$translation, $isNew] = $this->findOrCreateTranslation('demo-review-bao-cao-thi-truong-q3', 'Báo cáo thị trường quý 3/2026 (Demo — chờ trưởng phòng nội dung duyệt cuối)', $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitArticleForReviewAction::class)->handle($translation);

        Auth::login($editor);
        app(ApproveArticleTranslationAction::class)->handle($translation);
    }

    private function seedPublished(User $marketing, User $editor, User $head): void
    {
        [$translation, $isNew] = $this->findOrCreateTranslation('demo-review-huong-dan-su-dung-nen-tang', 'Hướng dẫn sử dụng nền tảng Hà Kiên (Demo — đã xuất bản)', $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitArticleForReviewAction::class)->handle($translation);

        Auth::login($editor);
        app(ApproveArticleTranslationAction::class)->handle($translation);

        Auth::login($head);
        app(PublishArticleAction::class)->handle($translation);
    }
}
