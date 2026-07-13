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
 * Approval Gateway — spec/Workflow_Approval_Technical_Specification.md §18.10). Từ v3.0
 * (spec/Platform_RBAC_Phase2_Specification.md §3.3 mục 6) đơn giản hoá tận gốc — Post không
 * còn tenant-scoped nên không cần bypass OrganizationScope + fetch/gán quan hệ `article` thủ
 * công như trước (workaround đó tồn tại chỉ vì eager-load mặc định từng bị OrganizationScope
 * của model liên quan làm rỗng kết quả với tài khoản xuyên tổ chức — nguyên nhân đó không còn).
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
