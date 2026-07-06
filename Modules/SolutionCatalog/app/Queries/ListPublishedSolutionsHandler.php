<?php

namespace Modules\SolutionCatalog\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\BusinessSolution\Enums\BusinessSolutionStatus;
use Modules\BusinessSolution\Enums\BusinessSolutionVisibility;
use Modules\BusinessSolution\Models\BusinessSolution;

/**
 * Đúng A02 §10.1 — "Marketplace không nên bán workflow, nên hiển thị Business Solution".
 * Chỉ đọc-only từ business_solutions (không có bảng riêng của module này, spec §5.1).
 */
class ListPublishedSolutionsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListPublishedSolutionsQuery $query */
        return BusinessSolution::query()
            ->where('status', BusinessSolutionStatus::Published->value)
            ->whereIn('visibility', [BusinessSolutionVisibility::Public->value, BusinessSolutionVisibility::Marketplace->value])
            ->with([
                'vertical', 'tags',
                'blueprints' => fn ($q) => $q->where('status', 'published'),
            ])
            ->when($query->verticalId, fn ($q) => $q->where('vertical_id', $query->verticalId))
            ->when($query->tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('tag', $query->tag)))
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderBy('name')
            ->get();
    }
}
