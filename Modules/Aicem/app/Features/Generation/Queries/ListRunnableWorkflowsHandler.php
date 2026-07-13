<?php

namespace Modules\Aicem\Features\Generation\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Aicem\Models\AicemWorkflow;

/**
 * Panel AICEM liệt kê workflow đang active có subject_type khớp trang hiện tại, lọc tiếp theo
 * `filters` (post_article: formats; product: category_ids) — null = áp dụng mọi bài/sản phẩm
 * (spec/AICEM_Technical_Specification.md mục 9).
 */
class ListRunnableWorkflowsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListRunnableWorkflowsQuery $query */
        $modelClass = config("aicem_subjects.{$query->subjectType}.model");

        if (! $modelClass) {
            return collect();
        }

        $subject = $modelClass::find($query->subjectId);

        if (! $subject) {
            return collect();
        }

        // post_article subject = PostArticleTranslation — format nằm ở PostArticle dùng chung
        // mọi ngôn ngữ (Publishing Engine Phase 13), không phải trên chính translation.
        if ($query->subjectType === 'post_article') {
            $subject->loadMissing('article');
        }

        // Lọc CỨNG theo tổ chức của subject (qua resolver — spec/Platform_RBAC_Phase2_Specification.md
        // §3.4, v3.0: Post không còn organization_id nên không thể đọc trực tiếp từ $subject nữa,
        // resolver trả về đúng 1 tổ chức cho mọi subject_type) — KHÔNG dựa vào TenantContext
        // ambient (cùng lý do đã ghi trong StartGenerationRunAction/RunAicemWorkflowJob):
        // super-admin bypass hoàn toàn OrganizationScope, nên nếu để AicemWorkflow::query() tự lọc
        // theo scope mặc định, panel sẽ hiện luôn workflow của MỌI Organization (mỗi org có bản sao
        // riêng 3 workflow mặc định cùng tên) — vừa hiện nút trùng lặp, vừa cho phép chọn nhầm
        // workflow khác Organization với subject, tạo ra run có organization_id/workflow_id lệch
        // nhau khiến RunAicemWorkflowJob throw TypeError (subject->workflow không tìm thấy do lệch
        // tenant) và bị kẹt mãi ở status=running (bug thật đã xảy ra, xem lịch sử).
        $resolver = app(config("aicem_subjects.{$query->subjectType}.resolver"));

        return AicemWorkflow::withoutTenant()
            ->where('organization_id', $resolver->organizationId($subject))
            ->where('subject_type', $query->subjectType)
            ->where('is_active', true)
            ->get()
            ->filter(fn (AicemWorkflow $workflow) => $this->matchesFilters($workflow, $query->subjectType, $subject))
            ->values();
    }

    private function matchesFilters(AicemWorkflow $workflow, string $subjectType, Model $subject): bool
    {
        $filters = $workflow->filters;

        if (empty($filters)) {
            return true;
        }

        return match ($subjectType) {
            'post_article' => empty($filters['formats']) || in_array($subject->article->format->value, $filters['formats'], true),
            'product'      => empty($filters['category_ids']) || in_array($subject->category_id, $filters['category_ids'], true),
            default        => true,
        };
    }
}
