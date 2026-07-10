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

        return AicemWorkflow::query()
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
            'post_article' => empty($filters['formats']) || in_array($subject->format->value, $filters['formats'], true),
            'product'      => empty($filters['category_ids']) || in_array($subject->category_id, $filters['category_ids'], true),
            default        => true,
        };
    }
}
