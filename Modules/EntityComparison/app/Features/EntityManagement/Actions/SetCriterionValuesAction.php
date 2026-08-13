<?php

namespace Modules\EntityComparison\Features\EntityManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Models\CriterionValue;
use Modules\EntityComparison\Models\Entity;
use Modules\EntityComparison\Support\CriterionValueResolver;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §7.1 mục 4 — ghi criterion_values/
 * criterion_value_options qua CriterionValueResolver, chỉ chấp nhận criterion đã gán cho
 * entity_type_id của $entity (không tin criterion_id gửi từ client — có thể không thuộc type).
 */
class SetCriterionValuesAction
{
    use AsAction;

    public function __construct(private readonly CriterionValueResolver $resolver) {}

    /** @param  array<int, mixed>  $values  keyed by criterion_id, đã validate ở FormRequest theo type */
    public function handle(Entity $entity, array $values): void
    {
        $assignedCriteria = $entity->entityType->criteria()->get()->keyBy('id');

        foreach ($values as $criterionId => $input) {
            $criterion = $assignedCriteria->get((int) $criterionId);

            if (! $criterion) {
                continue;
            }

            $existing = CriterionValue::query()
                ->where('entity_id', $entity->id)
                ->where('criterion_id', $criterion->id)
                ->first();

            $isEmpty = $input === null || $input === '' || (is_array($input) && $input === []);

            if ($isEmpty) {
                $existing?->delete();

                continue;
            }

            $value = $existing ?? new CriterionValue([
                'entity_id' => $entity->id,
                'criterion_id' => $criterion->id,
            ]);

            $this->resolver->write($value, $criterion, $input);
        }
    }
}
