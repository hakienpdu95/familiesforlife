<?php

namespace Modules\EntityComparison\Features\CriterionManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\EntityComparison\Enums\CriterionType;

/** Authorization thật nằm ở CriterionAdminController::authorizeResource() (constructor). */
class StoreCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_column(CriterionType::cases(), 'value'))],
            'unit' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_filterable' => ['boolean'],
            'is_comparable' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            // spec/Entity_Comparison_Module_Technical_Spec.md §0 mục 8 — chỉ có ý nghĩa khi
            // type ∈ {select, multi_select}; Action tự bỏ qua nếu type khác.
            'options' => ['array'],
            'options.*.value' => ['required_with:options', 'string', 'max:100'],
            'options.*.label' => ['required_with:options', 'string', 'max:150'],
            // 1 criterion — 1 entity type, bắt buộc.
            'entity_type_id' => ['required', 'integer', 'exists:entity_types,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên tiêu chí.',
            'type.required' => 'Vui lòng chọn kiểu dữ liệu.',
            'type.in' => 'Kiểu dữ liệu không hợp lệ.',
            'options.*.value.required_with' => 'Vui lòng nhập giá trị cho mỗi lựa chọn.',
            'options.*.label.required_with' => 'Vui lòng nhập nhãn hiển thị cho mỗi lựa chọn.',
            'entity_type_id.required' => 'Vui lòng chọn loại đối tượng.',
            'entity_type_id.exists' => 'Loại đối tượng không hợp lệ.',
        ];
    }
}
