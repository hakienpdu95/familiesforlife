<?php

namespace Modules\EntityComparison\Features\PublicFiltering\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\EntityComparison\Enums\CriterionType;
use Modules\EntityComparison\Models\Criterion;
use Modules\EntityComparison\Models\Entity;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.5.1 — filter range/number dùng đúng
 * value_number (>=) / value_number_max (<=) trực tiếp bằng SQL, không parse JSON.
 */
class ListEntitiesForPublicHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListEntitiesForPublicQuery $query */
        $criteria = Criterion::filterable()
            ->whereIn('id', array_keys($query->criterionFilters))
            ->get()
            ->keyBy('id');

        return Entity::query()
            ->ofType($query->entityTypeId)
            ->active()
            ->when(
                $query->criterionFilters,
                function (Builder $q) use ($query, $criteria) {
                    foreach ($query->criterionFilters as $criterionId => $filter) {
                        $criterion = $criteria->get((int) $criterionId);

                        if (! $criterion || $filter === null || $filter === '' || $filter === []) {
                            continue;
                        }

                        $this->applyCriterionFilter($q, $criterion, $filter);
                    }
                }
            )
            ->orderByDesc('sort_order')
            ->orderBy('name')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }

    private function applyCriterionFilter(Builder $query, Criterion $criterion, mixed $filter): void
    {
        $query->whereHas('criterionValues', function (Builder $q) use ($criterion, $filter) {
            $q->where('criterion_id', $criterion->id);

            match ($criterion->type) {
                CriterionType::Text => $q->where('value_string', 'like', '%'.$filter.'%'),
                CriterionType::Boolean => $q->where('value_bool', (bool) $filter),
                CriterionType::Date => $q->whereDate('value_date', $filter),
                CriterionType::Select => $q->where('option_id', (int) $filter),
                CriterionType::MultiSelect => $q->whereHas(
                    'options',
                    fn (Builder $qq) => $qq->whereIn('criterion_options.id', array_map('intval', (array) $filter))
                ),
                // isset() không đủ — true kể cả khi 'min'/'max' là chuỗi rỗng (bỏ trống 1 vế
                // trên form lọc), khi đó `where('value_number', '>=', '')` sẽ so sánh với 0 thay
                // vì bỏ qua điều kiện — âm thầm loại các giá trị âm/nhỏ hơn 0. Phải loại trừ ''.
                CriterionType::Number => $q
                    ->when(isset($filter['min']) && $filter['min'] !== '', fn (Builder $qq) => $qq->where('value_number', '>=', $filter['min']))
                    ->when(isset($filter['max']) && $filter['max'] !== '', fn (Builder $qq) => $qq->where('value_number', '<=', $filter['max'])),
                CriterionType::Range => $q
                    ->when(isset($filter['min']) && $filter['min'] !== '', fn (Builder $qq) => $qq->where('value_number', '>=', $filter['min']))
                    ->when(isset($filter['max']) && $filter['max'] !== '', fn (Builder $qq) => $qq->where('value_number_max', '<=', $filter['max'])),
            };
        });
    }
}
