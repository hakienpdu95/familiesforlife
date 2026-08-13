<?php

namespace Modules\EntityComparison\Features\CriterionManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Enums\CriterionType;
use Modules\EntityComparison\Features\CriterionManagement\Data\CriterionData;
use Modules\EntityComparison\Models\Criterion;
use Modules\EntityComparison\Models\CriterionOption;

class CreateCriterionAction
{
    use AsAction;

    public function handle(CriterionData $data): Criterion
    {
        $criterion = Criterion::create([
            'name' => $data->name,
            'slug' => $this->uniqueSlug($data->name),
            'type' => $data->type,
            'unit' => $data->unit,
            'description' => $data->description,
            'is_filterable' => $data->is_filterable,
            'is_comparable' => $data->is_comparable,
            'sort_order' => $data->sort_order,
            'created_by' => auth()->id(),
        ]);

        $this->syncOptions($criterion, $data);

        $criterion->entityTypes()->sync([$data->entity_type_id]);

        return $criterion;
    }

    /** spec/Entity_Comparison_Module_Technical_Spec.md §0 mục 8 — hàng thật ở criterion_options, KHÔNG phải JSON. */
    private function syncOptions(Criterion $criterion, CriterionData $data): void
    {
        if (! CriterionType::from($data->type)->hasOptions()) {
            return;
        }

        foreach ($data->options as $i => $option) {
            CriterionOption::create([
                'criterion_id' => $criterion->id,
                'value' => $option['value'],
                'label' => $option['label'],
                'sort_order' => $i,
            ]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Criterion::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
