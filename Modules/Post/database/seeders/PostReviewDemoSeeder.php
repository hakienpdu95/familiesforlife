<?php

namespace Modules\Post\Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\Concerns\ResolvesPlatformAccounts;
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
 * THẬT (không insert thẳng DB) do đúng vai (platform_content_creator tạo/gửi, editor duyệt sơ bộ, platform_content_head
 * duyệt cuối) để log/timestamp sinh ra thực tế.
 *
 * KHÔNG nằm trong SystemDataSeeder (demo-only, giống ProductApprovalDemoSeeder) — chạy thủ công:
 *   php artisan db:seed --class="Modules\Post\Database\Seeders\PostReviewDemoSeeder"
 *
 * Idempotent — mỗi bài demo dùng slug cố định, bỏ qua nếu đã tồn tại.
 */
class PostReviewDemoSeeder extends Seeder
{
    use ResolvesPlatformAccounts;

    public function run(): void
    {
        $accounts = $this->platformAccounts([
            'author' => RoleEnum::CONTENT_CREATOR,
            'editor' => RoleEnum::CONTENT_EDITOR,
            'head'   => RoleEnum::CONTENT_HEAD,
        ]);

        if (! $accounts) {
            return;
        }

        ['author' => $author, 'editor' => $editor, 'head' => $head] = $accounts;

        $previousUser = Auth::user();

        $this->seedDraft($author);
        $this->seedSubmitted($author, 'demo-review-cham-soc-khach-hang', 'Bí quyết chăm sóc khách hàng hiệu quả (Demo — chờ biên tập viên)');
        $this->seedSubmitted($author, 'demo-review-toi-uu-chi-phi', '5 mẹo tối ưu chi phí vận hành doanh nghiệp (Demo — chờ biên tập viên)');
        $this->seedApproved($author, $editor);
        $this->seedPublished($author, $editor, $head);

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

    private function seedDraft(User $author): void
    {
        // Không transition gì thêm — mặc định Draft ngay sau khi tạo, chưa gửi duyệt.
        $this->findOrCreateTranslation('demo-review-xu-huong-marketing-2026', 'Xu hướng marketing số 2026 (Demo — Draft, chưa gửi duyệt)', $author);
    }

    private function seedSubmitted(User $author, string $slug, string $title): void
    {
        [$translation, $isNew] = $this->findOrCreateTranslation($slug, $title, $author);
        if (! $isNew) {
            return;
        }

        Auth::login($author);
        app(SubmitArticleForReviewAction::class)->handle($translation);
    }

    private function seedApproved(User $author, User $editor): void
    {
        [$translation, $isNew] = $this->findOrCreateTranslation('demo-review-bao-cao-thi-truong-q3', 'Báo cáo thị trường quý 3/2026 (Demo — chờ trưởng phòng nội dung duyệt cuối)', $author);
        if (! $isNew) {
            return;
        }

        Auth::login($author);
        app(SubmitArticleForReviewAction::class)->handle($translation);

        Auth::login($editor);
        app(ApproveArticleTranslationAction::class)->handle($translation);
    }

    private function seedPublished(User $author, User $editor, User $head): void
    {
        [$translation, $isNew] = $this->findOrCreateTranslation('demo-review-huong-dan-su-dung-nen-tang', 'Hướng dẫn sử dụng nền tảng Hà Kiên (Demo — đã xuất bản)', $author);
        if (! $isNew) {
            return;
        }

        Auth::login($author);
        app(SubmitArticleForReviewAction::class)->handle($translation);

        Auth::login($editor);
        app(ApproveArticleTranslationAction::class)->handle($translation);

        Auth::login($head);
        app(PublishArticleAction::class)->handle($translation);
    }
}
