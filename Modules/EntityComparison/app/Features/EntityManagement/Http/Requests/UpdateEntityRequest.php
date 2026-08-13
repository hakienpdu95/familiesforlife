<?php

namespace Modules\EntityComparison\Features\EntityManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\EntityComparison\Enums\CriterionType;
use Modules\EntityComparison\Models\EntityType;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §9 — lớp 1/2 của defense-in-depth (lớp 2 nằm ở
 * UpdateEntityAction). Authorization thật nằm ở EntityAdminController::authorizeResource().
 */
class UpdateEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'entity_type_id' => [
                'required',
                'integer',
                Rule::exists('entity_types', 'id')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'cover' => ['nullable', 'image', 'max:10240'],
            'criterion_values' => ['array'],
        ];

        return array_merge($rules, $this->criterionValueRules());
    }

    public function messages(): array
    {
        return [
            'entity_type_id.required' => 'Vui lòng chọn loại đối tượng.',
            'entity_type_id.exists' => 'Loại đối tượng không hợp lệ hoặc đã bị xóa.',
            'name.required' => 'Vui lòng nhập tên đối tượng.',
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function criterionValueRules(): array
    {
        $entityTypeId = (int) $this->input('entity_type_id');

        if (! $entityTypeId) {
            return [];
        }

        $criteria = EntityType::find($entityTypeId)?->criteria ?? collect();
        $rules = [];

        foreach ($criteria as $criterion) {
            $key = "criterion_values.{$criterion->id}";
            $required = $criterion->pivot->is_required ? 'required' : 'nullable';

            $rules[$key] = match ($criterion->type) {
                CriterionType::Text => [$required, 'string', 'max:255'],
                CriterionType::Number => [$required, 'numeric'],
                CriterionType::Boolean => [$required, 'boolean'],
                CriterionType::Date => [$required, 'date'],
                CriterionType::Select => [$required, 'integer', Rule::exists('criterion_options', 'id')->where('criterion_id', $criterion->id)],
                CriterionType::MultiSelect => [$required, 'array'],
                CriterionType::Range => [$required, 'array'],
            };

            if ($criterion->type === CriterionType::MultiSelect) {
                $rules["{$key}.*"] = ['integer', Rule::exists('criterion_options', 'id')->where('criterion_id', $criterion->id)];
            }

            if ($criterion->type === CriterionType::Range) {
                $rules["{$key}.min"] = ['nullable', 'numeric'];
                $rules["{$key}.max"] = ['nullable', 'numeric', 'gte:'.$key.'.min'];
            }
        }

        return $rules;
    }
}
