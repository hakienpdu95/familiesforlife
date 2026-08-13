<?php

namespace Modules\EntityComparison\Features\Comparison\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Models\Criterion;
use Modules\EntityComparison\Models\Entity;
use Modules\EntityComparison\Models\EntityType;
use Modules\EntityComparison\Support\CriterionValueResolver;
use Modules\EntityComparison\Support\CriterionValueResult;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §7.2 bước 3 — eager-load toàn bộ quan hệ tránh
 * N+1 (Model::shouldBeStrict() chặn lazy-load ngoài production), chỉ criteria is_comparable=true,
 * kèm withTrashed() để bảng so sánh cũ (share link — §9) vẫn hiện được criterion đã xóa mềm.
 */
class BuildComparisonTableAction
{
    use AsAction;

    public function __construct(private readonly CriterionValueResolver $resolver) {}

    /**
     * @param  array<int, string>  $entityUuids  giữ đúng thứ tự người dùng đã chọn (§7.2 bước 2)
     * @return array{entities: Collection<int, Entity>, rows: array<int, array{criterion: Criterion, cells: array<string, string>}>}
     */
    public function handle(EntityType $entityType, array $entityUuids): array
    {
        $entities = Entity::query()
            ->ofType($entityType->id)
            ->whereIn('uuid', $entityUuids)
            ->with(['criterionValues.criterion', 'criterionValues.option', 'criterionValues.options'])
            ->get()
            ->sortBy(fn (Entity $entity) => array_search($entity->uuid, $entityUuids, true))
            ->values();

        $criteria = Criterion::withTrashed()
            ->whereHas('entityTypes', fn ($q) => $q->where('entity_types.id', $entityType->id))
            ->where('is_comparable', true)
            ->orderBy('sort_order')
            ->get();

        $rows = $criteria->map(function (Criterion $criterion) use ($entities) {
            return [
                'criterion' => $criterion,
                'cells' => $entities->mapWithKeys(function (Entity $entity) use ($criterion) {
                    $value = $entity->criterionValues->firstWhere('criterion_id', $criterion->id);
                    $result = $value
                        ? $this->resolver->read($value, $criterion)
                        : CriterionValueResult::scalar(null);

                    return [$entity->uuid => $this->resolver->format($result, $criterion)];
                })->all(),
            ];
        })->all();

        return ['entities' => $entities, 'rows' => $rows];
    }
}
