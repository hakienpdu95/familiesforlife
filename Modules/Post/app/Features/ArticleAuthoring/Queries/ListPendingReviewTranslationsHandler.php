<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\OrganizationScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;

/**
 * Hàng chờ duyệt bài viết XUYÊN TỔ CHỨC cho content_editor/content_head (Platform Approval
 * Gateway — spec/Workflow_Approval_Technical_Specification.md §18.10). Khác
 * ListArticlesForAdminHandler (lọc theo 1 tổ chức qua TenantContext) — handler này CỐ Ý bỏ
 * OrganizationScope để thấy bản dịch đang chờ duyệt của MỌI tổ chức.
 *
 * Lấy cả Submitted (chờ content_editor) VÀ Approved (chờ content_head) — mỗi item lọc lại
 * bằng Gate theo đúng ability tương ứng, để content_editor chỉ thấy việc của mình,
 * content_head thấy cả 2 (làm được việc của content_editor, §18.10).
 */
class ListPendingReviewTranslationsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListPendingReviewTranslationsQuery $query */
        $user = $query->user;

        $translations = PostArticleTranslation::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->whereIn('status', [TranslationStatus::Submitted, TranslationStatus::Approved])
            ->orderByDesc('updated_at')
            ->get();

        if ($translations->isEmpty()) {
            return collect();
        }

        // Tự fetch 'article' + 'article.organization' thủ công (bỏ OrganizationScope tường
        // minh) thay vì eager-load with() mặc định — eager-load morphTo/belongsTo mặc định
        // vẫn áp OrganizationScope của chính model liên quan, làm rỗng kết quả với tài khoản
        // xuyên tổ chức (đúng bug đã gặp ở ApprovalDashboardService::pendingForModerator(),
        // §18.4.2).
        $articles = PostArticle::withoutGlobalScope(OrganizationScope::class)
            ->whereIn('id', $translations->pluck('article_id')->unique())
            ->with('organization')
            ->get()
            ->keyBy('id');

        $translations->each(fn (PostArticleTranslation $t) => $t->setRelation('article', $articles->get($t->article_id)));

        return $translations->filter(function (PostArticleTranslation $t) use ($user) {
            if (! $t->article) {
                return false;
            }

            return match ($t->status) {
                TranslationStatus::Submitted => Gate::forUser($user)->allows('approve', $t),
                TranslationStatus::Approved  => Gate::forUser($user)->allows('publish', $t),
                default => false,
            };
        })->values();
    }
}
