<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticleTranslation;

/**
 * Hàng chờ duyệt bài viết cho platform_content_editor/platform_content_head (Platform
 * Approval Gateway — spec/Workflow_Approval_Technical_Specification.md §18.10).
 *
 * Lấy cả Submitted (chờ platform_content_editor) VÀ Approved (chờ platform_content_head) — mỗi item lọc lại
 * bằng Gate theo đúng ability tương ứng, để platform_content_editor chỉ thấy việc của mình,
 * platform_content_head thấy cả 2 (làm được việc của platform_content_editor, §18.10).
 */
class ListPendingReviewTranslationsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListPendingReviewTranslationsQuery $query */
        $user = $query->user;

        $translations = PostArticleTranslation::query()
            ->whereIn('status', [TranslationStatus::Submitted, TranslationStatus::Approved])
            ->with('article')
            ->orderByDesc('updated_at')
            ->get();

        return $translations->filter(fn (PostArticleTranslation $t) => match ($t->status) {
            TranslationStatus::Submitted => Gate::forUser($user)->allows('approve', $t),
            TranslationStatus::Approved  => Gate::forUser($user)->allows('publish', $t),
            default => false,
        })->values();
    }
}
