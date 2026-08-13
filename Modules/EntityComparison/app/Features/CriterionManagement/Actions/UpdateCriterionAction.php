<?php

namespace Modules\EntityComparison\Features\CriterionManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Enums\CriterionType;
use Modules\EntityComparison\Features\CriterionManagement\Data\CriterionData;
use Modules\EntityComparison\Models\Criterion;
use Modules\EntityComparison\Models\CriterionOption;

class UpdateCriterionAction
{
    use AsAction;

    public function handle(Criterion $criterion, CriterionData $data): Criterion
    {
        // Đổi `type` sau khi đã có criterion_values thực tế là quyết định phá dữ liệu (cột lưu
        // trữ khác nhau theo type — §3.5) — Action vẫn cho phép ở v1.0 (không validate chặn),
        // admin tự chịu trách nhiệm; validate chặt hơn để phase sau nếu cần.
        $criterion->update([
            'name' => $data->name,
            'type' => $data->type,
            'unit' => $data->unit,
            'description' => $data->description,
            'is_filterable' => $data->is_filterable,
            'is_comparable' => $data->is_comparable,
            'sort_order' => $data->sort_order,
            'updated_by' => auth()->id(),
        ]);

        $this->syncOptions($criterion, $data);

        $criterion->entityTypes()->sync([$data->entity_type_id]);

        return $criterion;
    }

    /**
     * Match theo `value` (khoá tự nhiên, unique theo criterion_id — §3.4) để GIỮ nguyên id của
     * option còn dùng, tránh orphan không cần thiết `criterion_values.option_id`
     * (nullOnDelete)/`criterion_value_options` (cascadeOnDelete) của các Entity đã nhập giá trị
     * chỉ vì sửa label 1 option có sẵn.
     */
    private function syncOptions(Criterion $criterion, CriterionData $data): void
    {
        if (! CriterionType::from($data->type)->hasOptions()) {
            $criterion->options()->delete();

            return;
        }

        $keepValues = collect($data->options)->pluck('value')->all();
        $criterion->options()->whereNotIn('value', $keepValues)->delete();

        foreach ($data->options as $i => $option) {
            CriterionOption::updateOrCreate(
                ['criterion_id' => $criterion->id, 'value' => $option['value']],
                ['label' => $option['label'], 'sort_order' => $i],
            );
        }
    }
}
